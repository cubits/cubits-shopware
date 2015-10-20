<?php
class  Shopware_Controllers_Frontend_PaymentCubits extends Shopware_Controllers_Frontend_Payment{

  public function indexAction()
  {
    return $this->redirect(array('action' => 'direct', 'forceSecure' => true));
  }

  public function directAction()
  {
    $router = $this->Front()->Router();
    $key = Shopware()->Config()->cubits_key;
    $secret = Shopware()->Config()->cubits_secret;
    $uid = $this->createPaymentUniqueId();
    $tid = $this->createPaymentUniqueId();
    require_once Shopware()->Plugins()->Frontend()->CubitsPaymentProvider()->Path().'lib/cubits-php/lib/Cubits.php';
    Cubits::configure("https://pay.cubits.com/api/v1/",true);
    $cubits = Cubits::withApiKey($key, $secret);
    $basket = $this->getBasket();
    $names = array();
    $description = array();
    foreach ($basket['content'] as $key => $item) {
      $description[] = $item['quantity'].' x '.$item['articlename'];

    }
    $description = implode('<br/>', $description);
    $name = 'Order id: '.substr($uid.'_'.$tid, 0, 7);

    if (strlen($description) > 512){
      $description = substr($description, 0, 509).'...';
    }
    $options = array(
      'callback_url'  => $router->assemble(array('action' => 'callback', 'forceSecure' => true, 'appendSession' => true)),
      'success_url'   => $router->assemble(array('action' => 'success', 'forceSecure' => true, 'uid' => $uid, 'tid' => $tid, 'appendSession' => true)),
      'cancel_url'    => $router->assemble(array('action' => 'cancel', 'forceSecure' => true)),
      'reference'     => $uid.'_'.$tid,
      'description'   => $description
    );

    $response = $cubits->createInvoice($name, $this->getAmount(), $this->getCurrencyShortName(), $options);
    $this->redirect($response->invoice_url);
  }

  public function successAction($args){    
    return $this->forward('finish', 'checkout', null, array('sUniqueID' => $this->Request()->get('uid')));
  }

  public function cancelAction(){
    $this->redirect(array(
        'controller' => 'checkout',
        'action' => 'payment'
    ));
  }

  public function callbackAction(){
    Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
    $key = Shopware()->Config()->cubits_key;
    $secret = Shopware()->Config()->cubits_secret;
    require_once Shopware()->Plugins()->Frontend()->CubitsPaymentProvider()->Path().'lib/cubits-php/lib/Cubits.php';

    Cubits::configure("https://pay.cubits.com/api/v1/",true);
    $cubits = Cubits::withApiKey($key, $secret);

    $params = json_decode(file_get_contents('php://input'));
    $payment_id = $params->id;
    $ids = explode('_', $params->reference);

    print_r($invoice_data);
    $sUniqueID = $ids[0];
    $transaction_id = $ids[1];

    $invoice_data = $cubits->getInvoice($payment_id);
    $sUniqueID_proof = $invoice_data->reference;

    if($params->reference == $sUniqueID_proof){
      switch ($invoice_data->status) {
      case 'completed':
      case 'overpaid':
        $state = 12;
      break;
      case 'underpaid':
        $state = 11;
      break;
      case 'pending':
      case 'unconfirmed':
        $state = 0;
      break;
      case 'aborted':
      case 'timeout':
          $state = -1;
      break;
      }

      $this->saveOrder(
        $transaction_id,
        $sUniqueID
      );

      $this->savePaymentStatus(
        $transaction_id,
        $sUniqueID,
        $state
      );

      print_r($invoice_data);

    }
  }


}
