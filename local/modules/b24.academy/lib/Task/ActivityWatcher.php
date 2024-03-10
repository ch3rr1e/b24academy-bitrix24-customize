<?php

namespace B24\Academy\Task;

use Bitrix\Forum\Comments\Comment;
use Bitrix\Forum\MessageTable;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\Date;
use Bitrix\Tasks\Internals\TaskTable;

class ActivityWatcher
{
    const TASKS_PER_STEP = 10;
    const AUDIT_TYPE = 'B24_ACADEMY_AGENTS';

    public static function runAgent(int $lastProcessedId = 0): string {
        Loader::requireModule('tasks');
        Loader::requireModule('forum');

        try {
            $tasks = self::getUncommentedTasks($lastProcessedId);
            if (empty($tasks)) {
                self::rescheduleAgent();
                return '';
            }
        } catch (\Exception $e) {
            self::log($e->getMessage());
            return __METHOD__ . "($lastProcessedId);";
        }

        foreach ($tasks as $task) {
            try {
                $lastProcessedId = $task['TASK_ID'];
                \CTaskCommentItem::add(
                    \CTaskItem::getInstance($task['TASK_ID'], $task['CREATED_BY']),
                    [
                        'AUTHOR_ID' => $task['CREATED_BY'],
                        'POST_MESSAGE' => Loc::getMessage('TASK_PING_MESSAGE', [
                            '#RESPONSIBLE#' => $task['RESPONSIBLE_NAME']
                        ]),
                        'UF_TASK_COMMENT_TYPE' => \Bitrix\Tasks\Comments\Internals\Comment::TYPE_PING_STATUS,
                    ]
                );
            } catch (\Exception $e) {
                self::log(
                    Loc::getMessage('TASK_COMMENT_ADD_ERROR', ['#DESCRIPTION#' => $e->getMessage()]),
                    $task['TASK_ID'],
                );
            }
        }

        return __METHOD__ . "($lastProcessedId);";
    }

    public static function getUncommentedTasks(int $lastProcessedId): array {
        $weekAgo = Date::createFromPhp(new \DateTime('-1 week midnight'));
        $result = TaskTable::getList([
            'select' => ['TASK_ID' => 'ID', 'CREATED_BY', 'RESPONSIBLE_ID', 'RESPONSIBLE_NAME'],
            'filter' => [
                '>TASK_ID' => $lastProcessedId,
                '<CREATED_DATE' => $weekAgo,
                'STATUS' => [
                    \CTasks::STATE_PENDING,
                    \CTasks::STATE_IN_PROGRESS,
                    \CTasks::STATE_SUPPOSEDLY_COMPLETED,
                    \CTasks::STATE_DEFERRED,
                ],
                'ZOMBIE' => 'N',
                'COMMENTS_COUNT' => 0,
            ],
            'runtime' => [
                new ReferenceField(
                    'MSG',
                    MessageTable::class,
                    Join::on('this.FORUM_TOPIC_ID', 'ref.TOPIC_ID')
                        ->where('ref.NEW_TOPIC', 'N')
                        ->where('ref.POST_DATE', '>', $weekAgo),
                    ['join_type' => Join::TYPE_LEFT],
                ),
                new ExpressionField('COMMENTS_COUNT', 'COUNT(DISTINCT %s)', 'MSG.ID'),
                new ExpressionField(
                    'RESPONSIBLE_NAME',
                    "CONCAT(%s, ' ', %s)",
                    ['RESPONSIBLE.NAME', 'RESPONSIBLE.LAST_NAME'],
                )
            ],
            'order' => ['TASK_ID' => 'ASC'],
            'limit' => self::TASKS_PER_STEP,
        ]);

        return $result->fetchAll();
    }

    private static function rescheduleAgent(): void {
        $agent = \CAgent::GetList(
            [],
            ['NAME' => ActivityWatcher::class . '::runAgent(%'],
        )->Fetch();
        if (!$agent) {
            throw new \Exception(Loc::getMessage('AGENT_NOT_FOUND'));
        }

        $deleteResult = \CAgent::Delete($agent['ID']);
        if (!$deleteResult) {
            throw new \Exception(Loc::getMessage('AGENT_DELETE_ERROR'));
        }

        $addResult = \CAgent::AddAgent(
            ActivityWatcher::class . '::runAgent();',
            'b24.academy',
            'Y',
            60,
            '',
            'Y',
            (new Date())->add('1d'),
        );
        if (!$addResult) {
            throw new \Exception(Loc::getMessage('AGENT_ADD_ERROR'));
        }
    }

    private static function log(string $message, ?int $taskId = null): void {
        \CEventLog::Add([
            'SEVERITY' => \CEventLog::SEVERITY_ERROR,
            'AUDIT_TYPE_ID' => self::AUDIT_TYPE,
            'MODULE_ID' => 'b24.academy',
            'ITEM_ID' => $taskId,
            'DESCRIPTION' => $message,
        ]);
    }
}