<?php
//To enable developer mode (no need for an RPC server, replace this file with the snipet at https://gist.github.com/d3e148deb5969c0e4b60

class BtcClient
{
  /**
   * @var string
   */
  private $uri;
  /**
   * @var JsonRpcClient
   */
  private $jsonRpc;

  /**
   * JdcClient constructor.
   * @param $host
   * @param $port
   * @param $user
   * @param $pass
   */
  function __construct($host, $port, $user, $pass)
  {
    $this->uri = "http://" . $user . ":" . $pass . "@" . $host . ":" . $port . "/";
    /**
     * @var JsonRpcClient
     */
    $this->jsonRpc = new JsonRpcClient($this->uri);
  }

  /**
   * omni send
   * @param $from
   * @param $to
   * @param $propertyid
   * @param $amount
   * @return mixed
   * @throws Exception
   */
  function omni_send($from, $to, $amount, $propertyid = 31)
  {
    return $this->jsonRpc->omni_send($from, $to, $propertyid, $amount);
  }

  /**
   * omni getbalance
   * @param $address
   * @param $propertyid
   * @return mixed
   * @throws Exception
   */
  function omni_getbalance($address, $propertyid = 31)
  {
    return $this->jsonRpc->omni_getbalance($address, $propertyid);
  }

  /**
   * omni get transaction by id
   * @param $txid
   * @return mixed
   * @throws Exception
   */
  function omni_gettransaction($txid)
  {
    return $this->jsonRpc->omni_gettransaction($txid);
  }

  /**
   * 获取钱包余额
   * @param $tag
   * @return mixed
   */
  function getBalance($tag)
  {
    return $this->jsonRpc->getbalance($tag, 6);
  }

//  function getBalanceAll()
//  {
//    // return $this->jsonRpc->getbalance($tag);
//    return $this->jsonRpc->getbalance();
//  }

  /**
   * 获取钱包地址列表
   * @param $tag
   * @return mixed|array
   * @throws Exception
   */
  function getAddressesByAccount($tag)
  {
    return $this->jsonRpc->getaddressesbyaccount($tag);
  }

//  /**
//   * 获取交易记录
//   * @param $tag
//   * @return mixed|array
//   */
//  function listTransactions($tag)
//  {
//    return $this->jsonRpc->listtransactions($tag, 100);
//  }

  /**
   * @param $tag
   * @return mixed|string
   */
  function getNewAddress($tag)
  {
    return $this->jsonRpc->getnewaddress($tag);
  }

  /**
   * 从指定Tag账户转出
   * @param $tag
   * @param $address
   * @param $amount
   * @return mixed
   */
  function sendFrom($tag, $address, $amount)
  {
    return $this->jsonRpc->sendfrom($tag, $address, (float)$amount, 6);
  }

  /**
   * 从当前钱包转出
   * @param $address
   * @param $amount
   * @return mixed
   */
  function sendToAddress($address, $amount)
  {
    return $this->jsonRpc->sendtoaddress($address, (float)$amount);
  }

  /**
   * 验证地址是否正确
   * {"isvalid":false}
   * @param $address
   * @return mixed|array
   */
  function validateAddress($address)
  {
    return $this->jsonRpc->validateaddress($address);
  }

  /**
   * 获取区块数据
   * @return mixed
   */
  function blockCount()
  {
    return $this->jsonRpc->getblockcount();
  }

  /**
   * 获取钱包所有信息
   * @return mixed
   */
  function getInfo()
  {
    return $this->jsonRpc->getinfo();
  }

  /**
   * 获取矿机信息
   * @return mixed
   */
  function getMiningInfo()
  {
    return $this->jsonRpc->getmininginfo();
  }
}
