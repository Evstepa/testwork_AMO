<?php

declare(strict_types=1);

require_once __DIR__ . '/src/AmoCrmV4Client.php';
require_once __DIR__ . '/AmoWork.php';

define('SUB_DOMAIN', 'elenavikstep');
define('CLIENT_ID', 'ec046140-f5e9-4ff3-9d77-5d3e99e227ed');
define('CLIENT_SECRET', 'rIpu1JxkiVeEYKRJZwbadBY6wJgChAOpO85pjYPIMTpu5hqN9cQC1atFIIOWu0st');
define('CODE', 'def502003040835cc0d73904bc7ea48b0cd1094704a5601e15dbc83d9d91f718bd911aacacd51a637829e8e6c6623a3acc7d688b7a1ef55d830379ea3a712a9773180a1c1c4b67c93eec718a6072b6999fc17f816bfecd2f0462bf8238e13517adcfa4d2823648bdc0a5d961ee639c86dd757437fa99e3e6e79e249e1db381235e98fcdab61b1b396a78c128d32802f025d547f96dd37eccc872e68cc9c750898911a7c5456f32df1daded149062c5f4dbceb72cd2d9ab2950e45fdf45a3fc1d8077c8c95368111ea337f957abd3c234eb2a5739dd2292669ea81eddd3e37d8f56273c9e0c8ebaa364e281842d37558391dd76da05adbdc5b78518c560e331dfdd00ea14e94c1c4520c5136f483fb13fa1c443f7f05b953a1f8e960ff256d490dfd586a4d6329de905b80247418d0c8dd3ea2eade37d87c07c317d399f294c4513485b618e515239250c855573738d3dfcdc1cb86d88f82eb1816b059bd7bc0f7baa482960cd4ef421919eccaac55dc3472e19a8a52e2a717bb0419cf68348470625100bc636725a9febef33a8ad19c45a29364c45f5012edc83a99f612b156a1e405c295826d29e21533b8f6bd229a280a98a3304ee5cce2e42981ffa7039af00fcfb9e4379eda0e867f9abfbbdbcdceabaf279c4e62ad2d212e1fbb3eb159ceaa8cf700069843c477b1f3649ad8d');
define('REDIRECT_URL', 'https://elenavikstep.amocrm.ru');

echo "<pre>";

try {
    $amoV4Client = new AmoCrmV4Client(SUB_DOMAIN, CLIENT_ID, CLIENT_SECRET, CODE, REDIRECT_URL);

    // создание экземпляра класс для решения поставленных задач
    $work = new AmoWork($amoV4Client);

    // задача 2 (бюджет сделки > 5000)
    $request = $work->transferByPrice(5000);

    // задача 3 (бюджет сделки = 4999)
    $work->copyByPrice(4999);
}

catch (Exception $ex) {
    var_dump($ex);
    file_put_contents("ERROR_LOG.txt", 'Ошибка: ' . $ex->getMessage() . PHP_EOL . 'Код ошибки:' . $ex->getCode());
}