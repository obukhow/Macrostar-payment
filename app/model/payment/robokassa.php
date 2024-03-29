<?php
namespace model\payment;
class Robokassa extends \F3instance
{
    protected $_testUrl = 'http://test.robokassa.ru/';

    protected $_stageUrl = 'https://merchant.roboxchange.com/';

    public $currency = "QiwiR";

    public $url, $password, $password2, $merchant, $order;

    protected $_code = 'robokassa';

    public function __construct()
    {
        if ($this->get('robokassa_test_mode')) {
            $this->url = $this->_testUrl;
        } else {
            $this->url = $this->_stageUrl;
        }
        $this->merchant = $this->get('robokassa_merchant');
        $this->password = $this->get('robokassa_password');
        $this->password2 = $this->get('robokassa_password2');
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }

    public function initialize()
    {
        $amount = $this->order->amount;
        $signature = array($this->merchant, $amount, $this->order->order_id, $this->password);
        $params = array(
            'MrchLogin'       => $this->merchant,
            'OutSum'          => $amount,
            'InvId'           => $this->order->order_id,
            'Desc'            => $this->order->song_title,
            'IncCurrLabel'    => $this->currency,
            'Email'           => $this->order->user_email,
            'SignatureValue'  => md5(implode(':', $signature)),
        );
        return $this->reroute($this->url . 'Index.aspx?' . http_build_query($params));
    }

    /**
     * Validate order on result url
     *
     * @param $order order
     *
     * @return boolean
     */
    public function validateOrder()
    {
        $orderId = $this->get("POST.InvId");
        $amount = $this->get("POST.OutSum");
        $signatureValue = $this->get("POST.SignatureValue");
       
        if ($this->order->dry()) {
            return false;
        }

        if ($this->order->amount > $amount) {
            return false;
        }
        $privateKey = array($amount, $orderId, $this->password2);
        if ($signatureValue != strtoupper(md5(implode(':', $privateKey)))) {
            return false;
        }

        return true;
    }
}