<?php
namespace controller;

class order extends \F3instance {
    const STATUS_NEW = 0;
    const STATUS_PAID = 1;
    const STATUS_CANCELED = 2;
    /**
     * Index action with qty selector
     *
     * @return void
     */
    public function indexAction()
    {
        if (!isset($_GET['user_id']) || !isset($_GET['song_id']) || !isset($_GET['song_url'])) {
            \F3::error(404);
        }
        $this->set('SESSION', array());
        $userId  = $_GET['user_id'];
        $songId  = $_GET['song_id'];
        $songUrl = str_replace('/load/', '/api/load/', $_GET['song_url']);
        $apikey  = $this->get('apikey');
        $songContent = \Web::http(
            'GET ' . $songUrl,
            http_build_query(
                array(
                    'apikey' => $apikey,
                )
            )
        );

        $userContent = \Web::http(
            'GET http://macrostar.ru/api/index/8-' . $userId,
            http_build_query(
                array(
                    'apikey' => $apikey,
                )
            )
        );
        $song = new \model\ucozObject($songContent);
        $this->set("Song", $song);

        $user = new \model\ucozObject($userContent);
        $this->set("User", $user);

        $this->set("SESSION.user", $user->getData());
        $this->set("SESSION.song", $song->getData());

        $this->set('content','index.htm');
        
        echo $this->render('basic/layout.htm');
    }

    /**
     * We are going to create an order
     *
     * @return void
     */
    public function createAction()
    {
        try {
            if (!$this->get('SESSION.user') || !$this->get('SESSION.song')) {
                throw new \Exception("Извините. Ваша сессия устарела. Закройте это окно и попробуйте осуществить покупку заново!", 1);
                
            }
            $this->set('DB',
                new \DB(
                    'mysql:host=' . $this->get('db_server') . ';port=3306;dbname=' . $this->get('db_name'),
                    $this->get('db_user'),
                    $this->get('db_pass')
                )
            );
            $amount = max($this->get('POST.amount'), $this->get('minAmount'));
            $order =  new \Axon('orders');
            $order->song_id = $this->get('SESSION.song.MATERIAL_ID');
            $order->song_title = $this->get('SESSION.song.TITLE');
            $order->owner_id = $this->get('SESSION.song.USER_ID');
            $order->song_url = $this->get('SESSION.song.ENTRY_URL');
            $order->user_id = $this->get('SESSION.user.USER_ID');
            $order->user_email = $this->get('SESSION.user.USER_EMAIL');
            $order->user_login = $this->get('SESSION.user.USER_USERNAME');
            $order->amount = $amount;
            $order->create_date = date("Y-m-d H:i:s");
            $order->status = self::STATUS_NEW;
            $order->payment_method = $this->get('POST.payment_method');
            $order->save();
            $this->set('SESSION.order', $order->_id);

            $this->clear('SESSION.song');
            $this->clear('SESSION.user');
            $this->set('content', 'preloader.htm');
            echo $this->render('basic/layout.htm');
        } catch (\Exception $e) {
            $this->set('SESSION.error', $e->getTraceAsString());
            $log = new \Log('exception.log');
            $log->write($e->getMessage());
            $log->write($e->getTraceAsString());
            $this->reroute('/order/error');
        }
    }

    public function payAction()
    {
        try {
            if (!$this->get('SESSION.order')) {
                throw new \Exception("Извините. Ваша сессия устарела. Закройте это окно и попробуйте осуществить покупку заново!", 1);
                
            }
            $this->set('DB',
                new \DB(
                    'mysql:host=' . $this->get('db_server') . ';port=3306;dbname=' . $this->get('db_name'),
                    $this->get('db_user'),
                    $this->get('db_pass')
                )
            );
            $order =  new \Axon('orders');
            $order->load("order_id='{$this->get('SESSION.order')}'");
            if ($order->status == self::STATUS_PAID) {
                $this->reroute('/order/success');
            }
            $this->clear('SESSION.order', $order->_id);
            $payment = \model\payment::getPayment($order);
            return $payment->initialize();

        } catch (\Exception $e) {
            $this->set('SESSION.error', $e->getTraceAsString());
            $log = new \Log('exception.log');
            $log->write($e->getMessage());
            $log->write($e->getTraceAsString());
            $this->reroute('/order/error');
        }
    }

    public function successAction()
    {
        $orderId = $this->get("SESSION.order");
        $this->set('order', $orderId);
        echo $orderId;
    }

    public function errorAction()
    {
        $message = $this->get('SESSION.error');
        $this->clear('SESSION.error');
        echo $message;
    }
}