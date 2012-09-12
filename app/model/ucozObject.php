<?php
namespace model;
class ucozObject {

    protected $_data = array();

    public function __construct($xml)
    {
        if (function_exists('xmlrpc_decode')) {
            $this->_data = xmlrpc_decode($xml);
        } else {
            $content = simplexml_load_string($xml);
            foreach ($content->params->param->value->struct->member as $item) { 
                $this->_data[(string)$item->name] = (string) (current(get_object_vars($item->value)));
            }
        }
    }

    public function getData()
    {
        return $this->_data;
    }

    /**
     * Magic get property function
     *
     * @param string $param param
     *
     * @return mixed
     */
    public function __get($param)
    {
        return (isset($this->_data[$param])) ? $this->_data[$param] : null;
    }

    /**
     * Magic set property value
     *
     * @param string $param param
     * @param mixed  $value value
     */
    public function __set($param, $value)
    {
        $this->_data[$param] = $value;
        return $this;
    }
}