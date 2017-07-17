<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class DeadlockProcessor implements MessageProcessorInterface
{
    const TABLE = 'gorgo_deadlock_test';

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        $this->createSchema();
        /** @var Connection $connection */
        $connection = $this->registry->getConnection();
        $connection->beginTransaction();

        $connection->executeQuery(
            'SELECT * FROM gorgo_deadlock_test WHERE id = :id FOR UPDATE',
            ['id' => $body['id1']],
            ['id' => Type::INTEGER]
        );

        usleep(75000);

        $connection->executeQuery(
            'SELECT * FROM gorgo_deadlock_test WHERE id = :id FOR UPDATE',
            ['id' => $body['id2']],
            ['id' => Type::INTEGER]
        );

        $connection->executeQuery(
            'UPDATE gorgo_deadlock_test SET body = :body WHERE id IN (:id1, :id2)',
            ['id1' => $body['id1'], 'id2' => $body['id2'], 'body' => (string) microtime(true)],
            ['id1' => Type::INTEGER, 'id2' => Type::INTEGER, 'body' => Type::TEXT]
        );

        $connection->commit();

        return self::ACK;
    }

    protected function createSchema()
    {
        /** @var Connection $connection */
        $connection = $this->registry->getConnection();
        $sm = $connection->getSchemaManager();
        if ($sm->tablesExist([self::TABLE])) {
            return;
        }

        $table = new Table(self::TABLE);
        $table->addColumn('id', 'integer', ['unsigned' => true]);
        $table->addColumn('body', 'text', ['notnull' => false,]);
        $table->setPrimaryKey(['id']);
        $sm->createTable($table);

        for ($i=1; $i <= 100; $i++) {
            $connection->insert(self::TABLE,[
                'id' => $i,
                'body' => (string) time()
            ]);
        }
    }
}
