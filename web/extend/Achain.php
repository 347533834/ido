<?php
//To enable developer mode (no need for an RPC server, replace this file with the snipet at https://gist.github.com/d3e148deb5969c0e4b60

/**
 * Class JdcClient
 * @package think
 */
class Achain
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
  function __construct($host, $port, $user, $pass, $version = '2.0')
  {
    $this->uri = "http://" . $user . ":" . $pass . "@" . $host . ":" . $port . "/rpc";
    /**
     * @var JsonRpcClient
     */
    $this->jsonRpc = new JsonRpcClient($this->uri, $version);
  }

  /**
   * rpc访问钱包登录函数
   * @param $params
   * @return mixed
   * @throws RPCException
   */
  function login($username, $password)
  {
    return $this->jsonRpc->{__FUNCTION__}([$username, $password]); // ['username' => $rpcuser, 'password' => $rpcpass]
  }

  /**
   * 显示版本号以及客户端的相关信息
   * @return mixed
   * @throws RPCException
   */
  function about()
  {
    return $this->jsonRpc->{__FUNCTION__}();
  }

  /**
   * 获取当前链以及钱包基础信息
   * @return mixed
   * @throws RPCException
   */
  function get_info()
  {
    return $this->jsonRpc->{__FUNCTION__}();
  }

  /**
   * 判断输入的公钥是否合法
   * @param $public_key
   * @return mixed
   * @throws RPCException
   */
  function validate_address($public_key)
  {
    return $this->jsonRpc->{__FUNCTION__}([$public_key]);
  }

  /**
   * 获取指定区块的全部详细交易信息
   * @param $block  要查询区块的区块id或者区块编号
   * @return mixed
   * @throws RPCException
   */
  function blockchain_get_block_transactions($block)
  {
    return $this->jsonRpc->{__FUNCTION__}([$block]);
  }

  /**
   * 获取指定帐号名称或ID 的账号详细信息
   * @param $account 账户名称或者要查询的账户id、账户地址以及账户公钥
   * @return
   * @throws RPCException
   */
  function blockchain_get_account($account)
  {
    return $this->jsonRpc->{__FUNCTION__}([$account]);
  }

  /**
   * 查询blockchain的信息和设定参数
   * @return mixed
   * @throws RPCException
   */
  function blockchain_get_info()
  {
    return $this->jsonRpc->{__FUNCTION__}();
  }

  /**
   * 列出被指定公钥拥有的balance
   * @param $public_key
   * @return mixed
   * @throws RPCException
   */
  function blockchain_list_key_balances($public_key)
  {
    return $this->jsonRpc->{__FUNCTION__}($public_key);
  }

  /**
   * 获取一笔区块链上转帐的详细信息交易
   * @param $trsid
   * @param bool $exact
   * @throws RPCException
   */
  function blockchain_get_transaction($trsid, $exact = false)
  {
    return $this->jsonRpc->{__FUNCTION__}($trsid, $exact);
  }

  /**
   * 以指定名称创建钱包
   * 调用前提：无
   * @param $wallet_name
   * @param $password
   * @return mixed
   * @throws RPCException
   */
  function wallet_create($wallet_name, $password)
  {
    return $this->jsonRpc->{__FUNCTION__}($wallet_name, $password); // ['wallet_name' => $wallet_name, 'password' => $password]
  }

  /**
   * 获取钱包信息
   * 调用前提：无
   * @return mixed
   * @throws RPCException
   */
  function wallet_get_info()
  {
    return $this->jsonRpc->{__FUNCTION__}();
  }

  /**
   * 如果目前的钱包是打开的就关闭它
   * 调用前提：无
   * @return mixed
   * @throws RPCException
   */
  function wallet_close()
  {
    return $this->jsonRpc->{__FUNCTION__}();
  }

  /**
   * 打开指定名称的钱包
   * 调用前提：当前指定名称的钱包名称已经存在
   * @param $wallet_name
   * @return mixed
   * @throws RPCException
   */
  function wallet_open($wallet_name)
  {
    return $this->jsonRpc->{__FUNCTION__}($wallet_name);
  }

  /**
   * 解锁钱包内的私钥以启用支付操作
   * 调用前提：钱包打开
   * @param $timeout    解锁过期时间
   * @param $password   密码
   * @return mixed
   * @throws RPCException
   */
  function wallet_unlock($timeout, $password)
  {
    return $this->jsonRpc->{__FUNCTION__}($timeout, $password);
  }

  /**
   * 将私钥导入本地钱包中, 并传回其所导入的真实帐号
   * @param $wif_key  要导入的私钥
   * @param $account  导入账户的名称
   * @param $creater  是否需要创建新账户
   * @param bool $scan 是否重新扫描新钱包
   * @return mixed
   * @throws RPCException
   */
  function wallet_import_private_key($wif_key, $account, $creater, $scan = true)
  {
    return $this->jsonRpc->{__FUNCTION__}($wif_key, $account, $creater, $scan);
  }

  /**
   * 将当前的钱包数据导出为一个JSON 文件
   * @param $filename 备份存储路径
   * @return mixed
   * @throws RPCException
   */
  function wallet_backup_create($filepath)
  {
    return $this->jsonRpc->{__FUNCTION__}($filepath);
  }

  /**
   * 获取本地钱包中或者注册到链上一个账户的地址
   * 调用前提：钱包打开
   * @param $account
   * @return mixed
   * @throws RPCException
   */
  function wallet_get_account_public_address($account)
  {
    return $this->jsonRpc->{__FUNCTION__}($account);
  }

  /**
   * 列出指定帐号的交易历史
   * @param $account  账户名称默认值是空
   * @param $asset    资产标示默认值是空
   * @param $limit    返回的交易数量上限默认值是0，0代表没有限制
   * @param $start    开始查询的block编号，默认值为0
   * @param $end      结束的block编号默认值为-1，代表着返回到当前块的交易
   * @return mixed
   * @throws RPCException
   */
  function wallet_account_transaction_history($account, $asset, $limit, $start, $end)
  {
    return $this->jsonRpc->{__FUNCTION__}($account, $asset, $limit, $start, $end);
  }

  /**
   * 根据出入账查询交易记录
   * @param $account    用户名
   * @param $asset      资产标识
   * @param $limit      条数限制
   * @param $type       交易类型
   * @return mixed
   * @throws RPCException
   */
  function wallet_transaction_history_splite($account, $asset, $limit, $type)
  {
    return $this->jsonRpc->{__FUNCTION__}($account, $asset, $limit, $type);
  }

  /**
   * 用备份的JSON 文件创建(复原)一个新的钱包
   * @param $filepath
   * @param $wallet_name
   * @param $password
   * @return mixed
   * @throws RPCException
   */
  function wallet_backup_restore($filepath, $wallet_name, $password)
  {
    return $this->jsonRpc->{__FUNCTION__}($filepath, $wallet_name, $password);
  }

  function wallet_check_address($address)
  {
    return $this->jsonRpc->{__FUNCTION__}($address);
  }

  /**
   * 创建一个账户
   * @param $account
   * @return mixed
   * @throws RPCException
   */
  function wallet_account_create($account)
  {
    return $this->jsonRpc->{__FUNCTION__}($account);;
  }

  /**
   * 转账到某个地址
   * 调用前提：钱包解锁，钱包内有账户
   * @param $amount     转账金额
   * @param $asset      转账资产类型
   * @param $from       取钱账户
   * @param $to         转账到账户的地址
   * @param $message    备注信息
   * @param $strategy   投票状态（vote_none不投票，vote_all投所有人最多108人,vote_radom随机投票，从支持者中随机选取一定的人数进行投票最多不超过36人， vote_recommended根据已经选择的投票人进行投票，如果选择的投票人的publish_data中有其他投票策略加入到自己的投票策略中）
   * @return mixed
   * @throws RPCException
   */
  function wallet_transfer_to_address($amount, $asset, $from, $to, $message, $strategy)
  {
    return $this->jsonRpc->{__FUNCTION__}($amount, $asset, $from, $to, $message, $strategy);
  }

  /**
   * 转账到某个账户
   * 调用前提：钱包解锁，钱包内有账户
   * @param $amount   转账金额
   * @param $asset    转账资产类型
   * @param $from     取钱账户
   * @param $to       转账目的账户
   * @param $message    备注信息
   * @param $strategy   投票状态（vote_none不投票，vote_all投所有人最多108人,vote_random随机投票，从支持者中随机选取一定的人数进行投票最多不超过36人， vote_recommended根据已经选择的投票人进行投票，如果选择的投票人的publish_data中有其他投票策略加入到自己的投票策略中）
   * @return mixed
   * @throws RPCException
   */
  function wallet_transfer_to_public_account($amount, $asset, $from, $to, $message, $strategy)
  {
    return $this->jsonRpc->{__FUNCTION__}($amount, $asset, $from, $to, $message, $strategy);
  }

  /**
   * 重新扫描区块，从中提取相关交易到钱包
   * 调用前提：钱包解锁
   * @return mixed
   * @throws RPCException
   */
  function wallet_rescan_blockchain()
  {
    return $this->jsonRpc->{__FUNCTION__}();
  }

  /**
   * 查询交易信息
   * 调用前提：钱包打开
   * @param $trid   交易单号
   * @return mixed
   * @throws RPCException
   */
  function wallet_get_transaction($trid)
  {
    return $this->jsonRpc->{__FUNCTION__}($trid);
  }

  /**
   * 账号注册
   * 调用前提：钱包解锁
   * @param $account    要注册的账户名
   * @param $from       支付本次注册费用的账户
   * @param $public     公开数据
   * @param $delegate   代理受托率
   * @param $type       账户类型(保留字段)
   * @return mixed
   * @throws RPCException
   */
  function wallet_account_register($account, $from, $public, $delegate, $type)
  {
    return $this->jsonRpc->{__FUNCTION__}($account, $from, $public, $delegate, $type);
  }

  /**
   * 查询钱包内所有相关账户信息
   * 调用前提：钱包打开
   * @return mixed
   * @throws RPCException
   */
  function wallet_list_accounts()
  {
    return $this->jsonRpc->{__FUNCTION__}();
  }

  /**
   * 获取指定账户余额
   * 调用前提：钱包打开
   * @param $account
   * @return mixed
   * @throws RPCException
   */
  function wallet_account_balance($account)
  {
    return $this->jsonRpc->{__FUNCTION__}($account);
  }

  /**
   * 获取当前节点娄和
   * @return mixed
   * @throws Exception
   */
  function blockchain_get_block_count()
  {
    return $this->jsonRpc->{__FUNCTION__}();
  }

  /**
   * 根据区块id获取区块信息
   * @param $id
   * @return mixed
   * @throws Exception
   */
  function blockchain_get_block($id)
  {
    return $this->jsonRpc->{__FUNCTION__}($id);
  }

  /**
   * 合约交易信息
   * @param $id
   * @return mixed
   * @throws Exception
   */
  function blockchain_get_pretty_contract_transaction($id)
  {
    return $this->jsonRpc->{__FUNCTION__}($id);
  }

  /**
   * 交易信息
   * @param $id
   * @return mixed
   * @throws Exception
   */
  function blockchain_get_pretty_transaction($id)
  {
    return $this->jsonRpc->{__FUNCTION__}($id);
  }

  /**
   * 获取交易id
   * @param $entryId
   * @return mixed { trx_id:'',block_num:0 }
   * @throws Exception
   */
  function blockchain_get_contract_result($entryId)
  {
    return $this->jsonRpc->{__FUNCTION__}($entryId);
  }

  /**
   * 获取交易信息
   * @param $blockNum
   * @param $trxId
   * @return mixed {id:'',event_type:'',event_param:'',is_truncated:false}
   * @throws Exception
   */
  function blockchain_get_event($blockNum, $trxId)
  {
    return $this->jsonRpc->{__FUNCTION__}($blockNum, $trxId);
  }

  // $asset['contract'], config('serchain'), 'transfer_to', $to . '|' . $amount, 'ACT', $asset['fee']

  /**
   * 调用合约
   * @param $contract 合约地址
   * @param $account 转出账号
   * @param string $transfer 交易方式 默认 transfer_to
   * @param $toAndAmount  接收地址|数量（精度为5）
   * @param string $act 固定为 ACT
   * @param $fee 手续费最高值，默认为0.02
   * @return mixed
   * @throws Exception
   */
  function call_contract($contract, $account, $transfer = 'transfer_to', $toAndAmount, $act = 'ACT', $fee = 0.02)
  {
    return $this->jsonRpc->{__FUNCTION__}($contract, $account, $transfer, $toAndAmount, $act, $fee);
  }

}
