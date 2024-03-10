<?php


use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;

defined('B_PROLOG_INCLUDED') || die;

/**
 * @property-write array $ObservedFields
 * @property-write int $ExpiresIn
 * @property-write array $UpdatedFields
 * @property-write bool $IsTimeout
 */
final class CBPPauseUntilChangeActivity extends BaseActivity implements IBPActivityExternalEventListener,
                                                                        IBPEventActivity
{
    private int $timerId;
    private array $documentSnapshot = [];

    public function __construct($name)
    {
        parent::__construct($name);

        $this->arProperties = [
            'ObservedFields' => [],
            'ExpiresIn' => 0,

            'UpdatedFields' => [],
            'IsTimeout' => null
        ];

        $this->setPropertiesTypes([
            'UpdatedFields' => [
                'Type' => FieldType::STRING,
                'Multiple' => true
            ],
            'IsTimeout' => [
                'Type' => FieldType::INT
            ]
        ]);
    }

    protected function prepareProperties(): void
    {
        parent::prepareProperties();

        $this->preparedProperties['IsTimeout'] = null;
        $this->preparedProperties['UpdatedFields'] = null;
    }

    protected function reInitialize()
    {
        parent::reInitialize();

        $this->IsTimeout = 0;
        $this->UpdatedFields = [];
        $this->documentSnapshot = [];
    }

    public function onExternalEvent($arEventParameters = []): void
    {
        try {
            if ($this->executionStatus == CBPActivityExecutionStatus::Closed) {
                return;
            }

            if (isset($arEventParameters['SchedulerService']) && $arEventParameters['SchedulerService'] == 'OnAgent') {
                $this->IsTimeout = 1;

                $this->log(Loc::getMessage('PAUSE_UNTIL_CHANGE_ACTIVITY_RESUMED_ON_TIMEOUT'));

                $this->unsubscribe($this);
                $this->workflow->closeActivity($this);

                return;
            }

            $documentState = $this->workflow->getRuntime()->getDocumentService()->getDocument($this->getDocumentId());
            $updatedFields = [];
            foreach ($this->documentSnapshot as $fieldName => $value) {
                $currentState = $documentState[$fieldName];

                if ($value != $currentState) {
                    $updatedFields[] = $fieldName;
                }
            }

            if (empty($updatedFields)) {
                return;
            }

            $this->UpdatedFields = $updatedFields;
            $observedFields = $this->ObservedFields;
            if (empty($observedFields)) {
                $this->unsubscribe($this);
                $this->workflow->closeActivity($this);

                return;
            }

            foreach ($observedFields as $observedField) {
                if (in_array($observedField, $updatedFields, true)) {
                    $this->unsubscribe($this);
                    $this->workflow->closeActivity($this);

                    return;
                }
            }
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    public function internalExecute(): ErrorCollection
    {
        $collection = parent::internalExecute();

        $this->documentSnapshot = $this->workflow
                ->getRuntime()
                ->getDocumentService()
                ->getDocument($this->getDocumentId());
        $this->subscribe($this);

        return $collection;
    }

    public function execute(): int
    {
        parent::execute();

        return CBPActivityExecutionStatus::Executing;
    }

    public function cancel(): int
    {
        $this->unsubscribe($this);

        return parent::cancel();
    }

    public function subscribe(IBPActivityExternalEventListener $eventHandler): void
    {
        $scheduler = $this->workflow->getRuntime()->getSchedulerService();
        $scheduler->subscribeOnEvent(
            $this->workflow->getInstanceId(),
            $this->name,
            'iblock',
            'OnAfterIblockElementUpdate',
        );
        if ($this->ExpiresIn > 0) {
            $this->timerId = $scheduler->subscribeOnTime(
                $this->workflow->getInstanceId(),
                $this->name,
                time() + $this->ExpiresIn,
            );
        }
        $this->workflow->addEventHandler($this->name, $eventHandler);
    }

    public function unsubscribe(IBPActivityExternalEventListener $eventHandler): void
    {
        $scheduler = $this->workflow->getRuntime()->getSchedulerService();

        $scheduler->unSubscribeOnEvent(
            $this->workflow->getInstanceId(),
            $this->name,
            'iblock',
            'OnAfterIblockElementUpdate',
        );
        if ($this->ExpiresIn > 0) {
            $scheduler->unSubscribeOnTime($this->timerId);
        }

        $this->workflow->removeEventHandler($this->name, $eventHandler);
    }

    protected static function getFileName(): string
    {
        return __FILE__;
    }

    /**
     * Посмотреть структуру массива, описывающего параметр действия, можно в методе
     * {@link FieldType::normalizeProperty}.
     *
     * @param PropertiesDialog|null $dialog
     * @return array[]
     */
    public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
    {
        $options = [];

        if ($dialog) {
            $fields = CBPRuntime::getRuntime()
                ->getDocumentService()
                ->getDocumentFields($dialog->getDocumentType());

            foreach ($fields as $id => $field) {
                $options[$id] = $field['Name'];
            }
        }

        return [
            'ObservedFields' => [
                'Type' => FieldType::SELECT,
                'Name' => Loc::getMessage('PAUSE_UNTIL_CHANGE_ACTIVITY_OBSERVED_FIELDS_PARAMETER'),
                'FieldName' => 'observed_fields',
                'Multiple' => true,
                'Required' => false,
                'Options' => $options
            ],
            'ExpiresIn' => [
                'Type' => FieldType::INT,
                'Name' => Loc::getMessage('PAUSE_UNTIL_CHANGE_ACTIVITY_EXPIRES_IN_PARAMETER'),
                'FieldName' => 'expires_in',
                'Multiple' => false,
                'Required' => false
            ]
        ];
    }
}