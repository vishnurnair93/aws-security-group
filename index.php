<?php

$ip =  trim(file_get_contents('http://checkip.amazonaws.com/'));
$last_ip = trim(file_get_contents('./last_ip.txt'));
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Aws\Credentials\CredentialProvider;

$ec2Client = new Aws\Ec2\Ec2Client([
    'region' => 'us-east-2',
    'version' => 'latest',
    'credentials' => CredentialProvider::env()
]);
try {

    if ($last_ip == $ip) {
        echo "Already exist";
        die();
    } else {
        $result = $ec2Client->revokeSecurityGroupEgress([
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
    }

    $result = $ec2Client->authorizeSecurityGroupIngress([
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


echo json_encode($result->toArray());
