<?php

$ip =  trim(file_get_contents('http://checkip.amazonaws.com/'));
$last_ip = trim(file_get_contents('./last_ip.txt'));
$result_revoke = [];
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Aws\Credentials\CredentialProvider;

$ec2Client = new Aws\Ec2\Ec2Client([
    'region' => getenv('REGION'),
    'version' => 'latest',
    'credentials' => CredentialProvider::env()
]);
try {

    if ($last_ip == $ip) {
        echo "Already exist";
        die();
    } else {
        if($last_ip != ''){
            $result_revoke = $ec2Client->revokeSecurityGroupIngress([
                'GroupId' => getenv('SECURITY_GROUP'),
                'IpPermissions' =>
                [
                    0 =>
                    [
                        'FromPort' => '0',
                        'IpProtocol' => 'tcp',
                        'ToPort' => '65535',
                        'IpRanges' =>
                        [
                            0 =>
                            [
                                'CidrIp' => $last_ip . '/32',
                                'Description' => getenv('USERNAME'),
                            ],
                        ],
                    ],
                ]
            ]);
        }
    }

    $result_authorize = $ec2Client->authorizeSecurityGroupIngress([
        'DryRun' => false,
        'GroupId' => getenv('SECURITY_GROUP'),
        'IpPermissions' =>
        [
            0 =>
            [
                'FromPort' => '0',
                'IpProtocol' => 'tcp',
                'ToPort' => '65535',
                'IpRanges' =>
                [
                    0 =>
                    [
                        'CidrIp' => $ip . '/32',
                        'Description' => getenv('USERNAME'),
                    ],
                ],
            ],
        ]
    ]);

    $file = fopen("./last_ip.txt", "w");
    fwrite($file, $ip);
    fclose($file);
} catch (\Exception $e) {
    echo $e->getMessage();
    die();
}

if($result_revoke){
    echo json_encode($result_revoke->toArray());
}
echo json_encode($result_authorize->toArray());

