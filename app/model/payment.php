<?php 
namespace model;
class payment {

    public static function getPayment($order)
    {
        $class = '\model\payment\\' . $order->payment_method;
        if (class_exists($class)) {
            $payment = new $class;
            $payment->setOrder($order);
            return $payment;
        }
        throw new Exception(sprintf("Class %s doesnt exists", $class), 1);
        
    } 
}