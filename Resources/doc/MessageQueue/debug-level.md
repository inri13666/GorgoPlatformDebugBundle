- added optional debug option for command oro:message-queue:consume to display all logs in console
### Example usage
```
$ app oro:message-queue:consume -vvv --debug
```
Output
```
[debug] Set context's logger Symfony\Component\Console\Logger\ConsoleLogger
[info] Start consuming
[debug] Switch to a queue oro.default
[debug] [CreateQueueExtension] Make sure the queue oro.default exists on a broker side.
[debug] [2017-06-25 21:02:04] doctrine.DEBUG: UPDATE oro_message_queue SET consumer_id=NULL, redelivered=:isRedelivered WHERE consumer_id IN (:consumerIds) {"isRedelivered":true,"consumerIds":["59501605141348.77257742","5950166cd28124.54881838"]} []

[alert] [RedeliverOrphanMessagesDbalExtension] Orphans were found and redelivered. consumerIds: "59501605141348.77257742, 5950166cd28124.54881838"
[info] Pre receive Message
[debug] [2017-06-25 21:02:04] doctrine.DEBUG: "START TRANSACTION" [] []

[debug] [2017-06-25 21:02:04] doctrine.DEBUG: SELECT id FROM oro_message_queue WHERE queue=:queue AND consumer_id IS NULL AND (delayed_until IS NULL OR delayed_until<=:delayedUntil) ORDER BY priority DESC, id ASC LIMIT 1 FOR UPDATE {"queue":"oro.default","delayedUntil":1498420924} []

[debug] [2017-06-25 21:02:04] doctrine.DEBUG: UPDATE oro_message_queue SET consumer_id=:consumerId  WHERE id = :messageId {"messageId":248,"consumerId":"595016bceae075.95288149"} []

[debug] [2017-06-25 21:02:04] doctrine.DEBUG: SELECT * FROM oro_message_queue WHERE consumer_id=:consumerId AND queue=:queue LIMIT 1 {"consumerId":"595016bceae075.95288149","queue":"oro.default"} []

[debug] [2017-06-25 21:02:04] doctrine.DEBUG: "COMMIT" [] []

[info] Message received
```
### Excluded logger channel
You can use command options `--debug-excluded=channel1,channel2` to filter by logger channel, for example you will see all the logs except doctrine
```
$ app oro:message-queue:consume -vvv --debug --debug-excluded=doctrine
```
