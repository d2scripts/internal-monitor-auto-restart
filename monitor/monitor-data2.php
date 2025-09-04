<?php

#DATA2-INTERNAL-CHECK-START

      #print_r($_REQUEST); #sleep(5);

$R = [];

$R['load'] = sys_getloadavg();

$R['pong'] = md5($_REQUEST['ping'] ?? '');

$R['services'] = [
    'mysql' => intval((bool)  (fsockopen('localhost', 3306,$a,$b,2))  ),
];


header("Content-type: application/json");

echo json_encode($R);

#DATA2-INTERNAL-CHECK-END
