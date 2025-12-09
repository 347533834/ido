<?php
/**
 * Created by PhpStorm.
 * User: feisha
 * Date: 2018/9/4
 * Time: 下午3:50
 */

class ExchApi
{

  private $url;
  private $id;

  function __construct($uri = '')
  {
    if (!$uri) $uri = config('Exch.USDT')['http'];
    $this->url = $uri;
    $this->id = time();
  }



  //////////////////// General

  /**
   * @return bool|mixed
   */
  function asset_list()
  {
    return $this->curl('asset.list');
  }

  /**
   * @return bool|mixed
   */
  function asset_summary()
  {
    return $this->curl('asset.summary');
  }

  /**
   * @return bool|mixed
   */
  function market_list()
  {
    return $this->curl('market.list');
  }

  /**
   * @return bool|mixed
   */
  function market_summary()
  {
    return $this->curl('market.summary');
  }

  //////////////////////// Balance

  /**
   * balance.query
   * {"id":5,"method":"balance.query","params":[1,"BTC","ETH"]}:
   * @param $user_id
   * @param array $assets
   * @return bool|mixed
   */
  function balance_query($user_id, $assets = 'BTC,USDT')
  {
    $data = [(int)$user_id];
    $assets = explode(',', $assets);
    foreach ($assets as $asset) {
//      if (empty($asset)) continue;
      $data[] = $asset;
    }

//    print_r(json_encode($data));
    return $this->curl('balance.query', $data);
  }

  /**
   * balance.update
   * {"id":6,"method":"balance.update","params":[1,"BTC","deposit",1,"1.5",{}]}
   * @param int $user_id
   * @param string $asset
   * @param string $business
   * @param int $business_id
   * @param string $amount
   * @return bool|mixed
   */
  function balance_update($user_id = 0, $asset = 'BTC', $business = "deposit", $business_id = 0, $amount = "0")
  {
    return $this->curl('balance.update', [(int)$user_id, $asset, $business, (int)$business_id, (string)$amount, (object)[]]);
  }

  /**
   * balance.history
   * {"id":7, "method":"balance.history", "params":[1,"BTC","deposit",0,0,0,50]}
   * @param $user_id
   * @param $asset
   * @param $business
   * @param $startTime
   * @param $endTime
   * @param $offset
   * @param $limit
   * @return bool|mixed
   */
  function balance_history($user_id, $asset, $business = "deposit", $startTime = 0, $endTime = 0, $offset = 0, $limit = 50)
  {
    return $this->curl('balance.history', [(int)$user_id, $asset, $business, (int)$startTime, (int)$endTime, (int)$offset, (int)$limit]);
  }

  ///////////////////////// Trading

  /**
   * order.put_limit
   * {"id":10,"method":"order.put_limit","params":[1,"BTCUSD",2,"1","8000","0.002","0.001","1"]}:
   * @param $user_id
   * @param $market BTCUSDT
   * @param $side 1ASK,2BID
   * @param $amount number
   * @param $price  number
   * @param $taker_fee  TakerFeeRate 0.001
   * @param $maker_fee  MakerFeeRate 0.001
   * @param $source
   * @return bool|mixed
   */
  function order_put_limit($user_id, $market = 'BTCUSDT', $side = 1, $amount = "0", $price = "0.00", $taker_fee = "0.00", $maker_fee = "0.00", $source = '')
  {
    return $this->curl('order.put_limit', [(int)$user_id, $market, (int)$side, (string)$amount, (string)$price, (string)$taker_fee, (string)$maker_fee, $source]);
  }

  /**
   * order.put_market
   * {"id":11,"method":"order.put_market","params":[1,"BTCUSD",2,"1","0.002",""]}:
   * @param $user_id
   * @param $market BTCUSDT
   * @param $side 1ASK,2BID
   * @param $amount
   * @param $taker_fee  TakerFeeRate 0.001
   * @param $source
   * @return bool|mixed
   */
  function order_put_market($user_id = 1, $market, $side = 1, $amount = "0.0", $taker_fee = "0.00", $source = '')
  {
    return $this->curl('order.put_market', [(int)$user_id, $market, (int)$side, (string)$amount, (string)$taker_fee, $source]);
  }

  /**
   * {"id":12,"method":"order.cancel","params":[1,"BTCUSD",1]}:
   * @param $user_id
   * @param $market BTCUSDT
   * @param $order_id
   * @return bool|mixed
   */
  function order_cancel($user_id, $market, $order_id)
  {
    return $this->curl('order.cancel', [(int)$user_id, $market, (int)$order_id]);
  }

  /**
   * {"id":13,"method":"order.deals","params":[1,0,50]}:
   * @param int $order_id
   * @param int $offset
   * @param int $limit
   * @return bool|mixed
   */
  function order_deals($order_id = 0, $offset = 0, $limit = 50)
  {
    return $this->curl('order.deals', [(int)$order_id, (int)$offset, (int)$limit]);
  }

  /**
   * order.book
   * {"id":14,"method":"order.book","params":["BTCUSD",2,0,50]}:
   * @param $market BTCUSDT
   * @param int $side 1ASK,2BID
   * @param int $offset
   * @param int $limit
   * @return bool|mixed
   */
  function order_book($market, $side = 1, $offset = 0, $limit = 50)
  {
    return $this->curl('order.book', [$market, (int)$side, (int)$offset, (int)$limit]);
  }

  /**
   * order.depth
   * {"id":15,"method":"order.depth","params":["BTCUSD",50,"1"]}
   * @param $market BTCUSDT
   * @param int $limit
   * @param int $interval
   * @return bool|mixed
   */
  function order_depth($market, $limit = 50, $interval = 1)
  {
    return $this->curl('order.depth', [$market, (int)$limit, (string)$interval]);
  }

  /**
   * order.pending
   * {"id":16,"method":"order.pending","params":[1,"BTCUSD",0,50]}
   * @param $user_id
   * @param string $market
   * @param int $offset
   * @param int $limit
   * @return bool|mixed
   */
  function order_pending($user_id, $market = 'BTCUSDT', $offset = 0, $limit = 50)
  {
    return $this->curl('order.pending', [(int)$user_id, $market, (int)$offset, (int)$limit]);
  }

  /**
   * order.pending_detail
   * {"id":17,"method":"order.pending_detail","params":["BTCUSD",1]}
   * @param string $market
   * @param $order_id
   * @return bool|mixed
   */
  function order_pending_detail($market = 'BTCUSDT', $order_id = 0)
  {
    return $this->curl('order.pending_detail', [$market, (int)$order_id]);
  }

  /**
   * order.finished
   * {"id":18,"method":"order.finished","params":[1,"BTCUSD",1,120000000,0,50,2]}
   * @param int $user_id
   * @param string $market
   * @param int $startTime
   * @param int $endTime
   * @param int $offset
   * @param int $limit
   * @param int $side
   * @return bool|mixed
   */
  function order_finished($user_id = 1, $market = 'BTCUSDT', $startTime = 0, $endTime = 0, $offset = 0, $limit = 50, $side = 1)
  {
    return $this->curl('order.finished', [(int)$user_id, $market, (int)$startTime, (int)$endTime, (int)$offset, (int)$limit, (int)$side]);
  }

  /**
   * order.finished_detail
   * {"id":19,"method":"order.finished_detail","params":[1]}
   * @param $order_id
   * @return bool|mixed
   */
  function order_finished_detail($order_id = 1)
  {
    return $this->curl('order.finished_detail', [(int)$order_id]);
  }

  //////////////// MARKET

  /**
   * market.last
   * {"id":21,"method":"market.last","params":["BTCUSD"]}:
   * @param string $market
   * @return bool|mixed
   */
  function market_last($market = 'BTCUSDT')
  {
    return $this->curl('market.last', [$market]);
  }

  /**
   * market.deals
   * {"id":22,"method":"market.deals","params":["BTCUSD",50,0]}:
   * @param string $market
   * @param int $limit
   * @param int $offset
   * @return bool|mixed
   */
  function market_deals($market = 'BTCUSDT', $limit = 50, $offset = 0)
  {
    return $this->curl('market.deals', [$market, (int)$limit, (int)$offset]);
  }

  /**
   *
   * {"id":23,"method":"market.user_deals","params":[1,"BTCUSD",0,50]}:
   * @param int $user_id
   * @param string $market
   * @param int $offset
   * @param int $limit
   * @return bool|mixed
   */
  function market_user_deals($user_id = 1, $market = 'BTCUSDT', $offset = 0, $limit = 50)
  {
    return $this->curl('market.user_deals', [(int)$user_id, $market, (int)$offset, (int)$limit]);
  }

  /**
   * market.kline
   * {"id":24,"method":"market.kline","params":["BTCUSD",1,12000000,3600]}:
   * @param string $market
   * @param int $startTime
   * @param int $endTime
   * @param int $interval
   * @return bool|mixed
   */
  function market_kline($market = 'BTCUSDT', $startTime = 0, $endTime = 0, $interval = 3600)
  {
    return $this->curl('market.kline', [$market, (int)$startTime, (int)$endTime, (int)$interval]);
  }

  /**
   * market.status
   * {"id":25,"method":"market.status","params":["BTCUSD",86400]}:
   * @param string $market
   * @param int $period
   * @return bool|mixed
   */
  function market_status($market = 'BTCUSDT', $period = 86400)
  {
    return $this->curl('market.status', [$market, (int)$period]);
  }

  /**
   * market.status_today
   * {"id":26,"method":"market.status_today","params":["BTCUSD"]}:
   * @param string $market
   * @return bool|mixed
   */
  function market_status_today($market = 'BTCUSDT')
  {
    return $this->curl('market.status_today', [$market]);
  }

  /**
   * CURL
   * @param $method
   * @param array $params
   * @param array $header
   * @return bool|mixed
   */
  private function curl($method, $params = [], $header = [])
  {
    $header = $header ? $header : [
      "Accept: application/json",
//      'Authorization:api-key: ' . $API_KEY . ':sign: ' . $sign
    ];

    $data = [
      'jsonrpc' => '2.0',
      'method' => $method,
      'params' => $params,
      'id' => $this->id
    ];

    $ch = curl_init($this->url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response ? json_decode($response, true) : false;
  }


}