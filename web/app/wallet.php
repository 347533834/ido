<?php
/**
 * WALLET rpc
 */
return array(
    /*
     * ETH Config
     */
    'ETH' => [
//    'host' => '192.168.1.89',
        'host' => '159.138.6.243',
        'port' => '8545',
        'root' => '0x77b3078086c24294187ad4a6d10af740bbcbaf55', // eth.accounts[0]
        'passwd' => 'CPFzJf9O8EGvRQ0t',  // unlock eth.accounts[0]
        'salt' => 'D5s6W3gF',
        'offline' => '',
    ],
    'AWT' => [
        'contact' => '0xe0c3d01744435b87eec98f01d58424b3bafbea22',
        'offline' => '',
    ],

    'USDT' => [
        'host' => '172.31.99.2',
        'port' => '8332',
        'user' => 'btcrpc',
        'root' => '1PFA3jfvnpfzfwbpfKvmEpDLhoZRgP7BZ7',
        'password' => '6bvQkZRyXQExp63Xw0bQW0Ak1GuZdnYD',
        'offline' => '',
        "account" => "",

    ],


);