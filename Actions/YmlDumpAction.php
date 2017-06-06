<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Actions;

use Oro\Component\Action\Action\AbstractAction;
use Symfony\Component\Yaml\Yaml;

class YmlDumpAction extends AbstractAction
{
    const OPTION_KEY_DATA = 'data';
    const OPTION_KEY_ATTRIBUTE = 'attribute';

    protected $attribute = 'yml';
    protected $filename = 'dump.yml';

    protected $data;

    /**
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        if (array_key_exists(self::OPTION_KEY_ATTRIBUTE, $options)) {
            $this->attribute = $options[self::OPTION_KEY_ATTRIBUTE];
        }

        if (array_key_exists(self::OPTION_KEY_DATA, $options)) {
            $this->data = $options[self::OPTION_KEY_DATA];
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function executeAction($context)
    {
        $data = $this->contextAccessor->getValue($context, $this->data);
        $yml = Yaml::dump($data);
        $this->contextAccessor->setValue($context, $this->attribute, sprintf(
            '<a href="data:application/octet-stream;base64,%s" download="%s">Download</a>',
            base64_encode($yml),
            $this->filename
        ));
    }
}
