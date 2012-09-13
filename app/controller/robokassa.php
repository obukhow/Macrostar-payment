<?php
namespace controller;

class robokassa extends \F3instance
{

    /**
     * Robokassa result action
     *
     * @return [type] [description]
     */
    public function resultAction()
    {
        try {
            $this->set('DB',
                new \DB(
                    'mysql:host=' . $this->get('db_server') . ';port=3306;dbname=' . $this->get('db_name'),
                    $this->get('db_user'),
                    $this->get('db_pass')
                )
            );
            $orderId = $this->get("POST.InvId");

            $order = new \Axon('orders');
            $order->load("order_id='$orderId'");

            $payment = \model\payment::getPayment($order);
            if (!$payment->validateOrder()) {
                throw new \Exception("Invalid order params", 1);       
            }
            $order->status = \controller\order::STATUS_PAID;
            $order->payment_date = date("Y-m-d H:i:s");
            $order->save();
            echo "OK" . $order->order_id;

        } catch (\Exception $e) {
            $log = new \Log('exception.log');
            $log->write($e->getMessage());
            $log->write($e->getTraceAsString());
            $this->reroute('/order/error');
            echo 'Fail'; die();
        }
    }

    /**
     * Robokassa success section
     *
     * @return [type] [description]
     */
    public function successAction()
    {
        try {
            $this->set('DB',
                new \DB(
                    'mysql:host=' . $this->get('db_server') . ';port=3306;dbname=' . $this->get('db_name'),
                    $this->get('db_user'),
                    $this->get('db_pass')
                )
            );
            $orderId = $this->get("POST.InvId");
            $amount = $this->get("POST.OutSum");
            $order = new \Axon('orders');
            $order->load("order_id='$orderId'");
            if ($order->dry()) {
                throw new \Exception("Invalid order id", 1);
            }
            if ($order->status != \controller\order::STATUS_PAID) {
                $this->set('SESSION.order', $order->order_id);
                return $this->reroute('/order/pay');
            }
            $this->set('SESSION.order', $order->order_id);
            $this->reroute('/order/success');

        } catch (\Exception $e) {
            $this->set("SESSION.error", $e->getMessage());
            $log = new \Log('exception.log');
            $log->write($e->getMessage());
            $log->write($e->getTraceAsString());
            $this->reroute('/order/error');
        }
    }

    public function errorAction()
    {
        try {
            $this->set('DB',
                new \DB(
                    'mysql:host=' . $this->get('db_server') . ';port=3306;dbname=' . $this->get('db_name'),
                    $this->get('db_user'),
                    $this->get('db_pass')
                )
            );
            $orderId = $this->get("POST.InvId");
            $amount = $this->get("POST.OutSum");
            $order = new \Axon('orders');
            $order->load("order_id='$orderId'");
            if ($order->dry()) {
                throw new \Exception("Invalid order id", 1);
            }
            $order->status = \controller\order::STATUS_CANCELED;
            $order->save();
            $this->set("SESSION.error", "Вы отказались от оплаты заказа. Вы можете вернуться к оплате в любое время, выбрав данный заказ на на вашей странице.");
        } catch (\Exception $e) {
            $this->set("SESSION.error", $e->getMessage());
            $log = new \Log('exception.log');
            $log->write($e->getMessage());
            $log->write($e->getTraceAsString());
        }
        return $this->reroute('/order/error');
    }

}