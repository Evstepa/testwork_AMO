<?php

declare(strict_types=1);

class AmoWork
{
    /**
     * ID воронки
     * @var int
     */
    private const AMO_PIPELINE = 8620490;

    /**
     * ID статуса "Заявка"
     * @var int
     */
    private const AMO_REQUEST = 69923262;

    /**
     * ID статуса "Ожидание клиента"
     * @var int
     */
    private const AMO_WAITING = 69923266;

    /**
     * ID статуса "Клиент подтвердил"
     * @var int
     */
    private const AMO_CONFIRMED = 69923270;

    /**
     * объект для выполнения запросов
     * @var AmoCrmV4Client
     */
    private $amoCRM;

    /**
     * массив сделок с привязанными к ним примечаниями и задачами
     * @var array
     */
    private $leadsObjects;

    public function __construct(AmoCrmV4Client $amoCRM)
    {
        $this->amoCRM = $amoCRM;
        $this->leadsObjects = [];
    }

    /**
     * получение списка сделок с заданным балансом
     * и создание списка данных для обновления сделок
     * @param array $list
     * @param int $price
     * @return array
     */
    private function getListByPrice(array $list, int $price, int $status): array
    {
        $newList = array_filter(
            $list,
            function ($item) use ($price)  {
                return $item['price'] > $price;
            }
        );

        $deals = [];
        foreach ($newList as $key => $value) {
            $deals[] = [
                'id' => $value['id'],
                'status_id' => $status,
            ];
        }

        return $deals;
    }

    /**
     * перенос сделок с заданным балансом в новый статус
     * @param int $price
     * @return array
     */
    public function transferByPrice(int $price): array
    {
        $startList = $this->amoCRM->GETRequestApi(
        "leads", [
            "filter[statuses][0][pipeline_id]" => $this::AMO_PIPELINE,
            "filter[statuses][0][status_id]" => $this::AMO_REQUEST,
        ])["_embedded"]["leads"];

        return $this->amoCRM->POSTRequestApi(
            "leads",
            $this->getListByPrice(
                $startList,
                $price,
                $this::AMO_WAITING
            ),
            "PATCH"
        );
    }

    /**
     * сохранение данных копий найденных сделок в массиве сделок
     * @param array $list
     * @param int $status
     * @return void
     */
    private function createCopy(array $list, int $status)
    {
        foreach ($list as $key => $item) {
            $this->leadsObjects[$item['id']] = [
                'original_lead_id' => $item['id'],
                'new_lead_id' => 0,
                'lead' => [
                    'name' => $item['name'] . ' (копия)',
                    'price' => $item['price'],
                    'status_id' => $status,
                    'created_by' => $item['created_by'],
                    'responsible_user_id' => $item['responsible_user_id'],
                    'closest_task_at' => $item['closest_task_at'],
                    'account_id' => $item['account_id'],
                    '_links' => $item['_links'],
                    '_embedded' => [
                        'tags' => $item['_embedded']['tags'],
                        'companies' => $this->getCompanies($item['_embedded']['companies']),
                    ],
                    'group_id' => $item['group_id'],
                ],
            ];
        }
    }

    /**
     * получение данных о компаниях (для копирования сделки)
     * @param array $companyList
     * @return array
     */
    private function getCompanies(array $companyList): array
    {
        $companies = [];
        foreach ($companyList as $key => $item) {
            $companies[] = [
                'id' => $item['id'],
            ];
        }
        return $companies;
    }

    /**
     * прикрепление всех примечаний к сделке для копирования (в массиве сделок)
     * @param array $allSubjects
     * @return void
     */
    private function getNotes(array $allSubjects)
    {
        foreach ($this->leadsObjects as $key1 => $deal) {
            $currentList = [];
            foreach ($allSubjects as $key2 => $subject) {
                if ($subject['entity_id'] === $deal['original_lead_id']) {
                    $currentList[] = [
                        'entity_id' => 0,
                        'created_by' => $subject['created_by'],
                        'responsible_user_id' => $subject['responsible_user_id'],
                        'note_type' => $subject['note_type'],
                        'group_id' => $subject['group_id'],
                        'params' => $subject['params'],
                        'account_id' => $subject['account_id'],
                    ];
                }
            }
            $this->leadsObjects[$key1]['notes'] = $currentList;
        }
    }

    /**
     * прикрепление всех задач к сделке для копирования (в массиве сделок)
     * @param array $allSubjects
     * @return void
     */
    private function getTasks(array $allSubjects)
    {
        foreach ($this->leadsObjects as $key1 => $deal) {
            $currentList = [];
            foreach ($allSubjects as $key2 => $subject) {
                if ($subject['entity_id'] === $deal['original_lead_id']) {
                    $currentList[] = [
                        'entity_id' => 0,
                        'created_by' => $subject['created_by'],
                        'responsible_user_id' => $subject['responsible_user_id'],
                        'complete_till' => $subject['complete_till'],
                        'text' => $subject['text'],
                        'group_id' => $subject['group_id'],
                        'entity_type' => $subject['entity_type'],
                        'duration' => $subject['duration'],
                        'account_id' => $subject['account_id'],
                        'is_completed' => $subject['is_completed'],
                        'task_type_id' => $subject['task_type_id'],
                        'result' => $subject['result'],
                    ];
                }
            }
            $this->leadsObjects[$key1]['tasks'] = $currentList;
        }
    }

    /**
     * создание копий сделок с заданным балансом
     * @param AmoCrmV4Client $amoV4Client
     * @param int $price
     */
    public function copyByPrice(int $price)
    {
        // запрос на получение сделок с заданным балансом
        $startList = $this->amoCRM->GETRequestApi(
        "leads", [
            "filter[statuses][0][pipeline_id]" => $this::AMO_PIPELINE,
            "filter[statuses][0][status_id]" => $this::AMO_CONFIRMED,
            "filter[price]" => $price,
        ]
        )["_embedded"]["leads"];

        // создание копий сделок в массиве сделок
        $this->createCopy(
            $startList,
            $this::AMO_WAITING
        );

        // привязка к сделкам их примечаний в массиве сделок
        $this->getNotes(
            $this->amoCRM->GETRequestApi(
                "leads/notes",
                []
            )["_embedded"]["notes"]
        );

        // привязка к сделкам их задач в массиве сделок
        $this->getTasks(
            $this->amoCRM->GETRequestApi(
                "tasks",
                []
            )["_embedded"]["tasks"]
        );

        // копирование сделок в новый статус
        // и запись новых ID сделок соответствующим примечаниям и задачам
        foreach ($this->leadsObjects as $key => $lead) {
            $this->leadsObjects[$key]['new_lead_id']
                = $this->amoCRM->POSTRequestApi(
                    "leads",
                    [$lead['lead']],
                    "POST"
                )["_embedded"]["leads"][0]['id'];

            // установка примечаниям ID сделки-копии
            foreach ($this->leadsObjects[$key]['notes'] as $key1 => $node) {
                $this->leadsObjects[$key]['notes'][$key1]['entity_id']
                    = $this->leadsObjects[$key]['new_lead_id'];
            }

            // установка задачам ID сделки-копии
            foreach ($this->leadsObjects[$key]['tasks'] as $key1 => $task) {
                $this->leadsObjects[$key]['tasks'][$key1]['entity_id']
                    = $this->leadsObjects[$key]['new_lead_id'];
            }
        }

        // сбор всех примечаний и задач для запроса на их создание
        $allNotes = [];
        $allTasks = [];
        foreach ($this->leadsObjects as $key => $lead) {
            $allNotes = array_merge($allNotes, $lead['notes']);
            $allTasks = array_merge($allTasks, $lead['tasks']);
        }

        // запрос на создание примечаний к копиям сделок
        $responseNotes = $this->amoCRM->POSTRequestApi(
            "leads/notes",
            $allNotes,
            "POST"
        );

        // запрос на создание задач к копиям сделок
        $responseTasks = $this->amoCRM->POSTRequestApi(
            "tasks",
            $allTasks,
            "POST"
        );
    }
}
