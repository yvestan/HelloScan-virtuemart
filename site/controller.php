<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 ff=unix fenc=utf8: */

/**
*
* HelloScan for Joomla
*
* @package HelloScan_Joomla
* @author Yves Tannier [grafactory.net]
* @copyright 2011 Yves Tannier
* @link http://helloscan.mobi
* @version 0.1
* @license MIT Licence
*/

// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
 
jimport('joomla.application.component.controller');


/**
 * HelloScan Component Controller
 *
 * @package    Virtuemart.Helloscan
 * @subpackage Components
 */
class HelloscanController extends JController
{

    // get product infos
    function scan() {

        // user parameters
        $HS_requestParams = new HelloScan_RequestParams();

        // response handler
        $HS_responseHandler = new HelloScan_ResponseHandler();

        // check key
        $HS_authKey = new HelloScan_AuthKey($HS_requestParams);

        if(!$HS_authKey->check()) {
            // send response and exit
            $HS_responseHandler->sendResponse(array(
                'status' => '401',
                'response' => 'Bad authorisation key'
            ));
        }

        // check product code
        if(!$HS_requestParams->codeExist()) {
            // send response and exit
            $HS_responseHandler->sendResponse(array(
                'status' => '404',
                'response' => 'Product code unvalaible'
            ));
        }

        // helloscan
        $HS_check = new HelloScan_Check($HS_requestParams);

        // method = action
        if(method_exists($HS_check,$HS_requestParams->getAction())) {
            // perform action
            $HS_actionResult = $HS_check->execute();
            // send result and exit
            $HS_responseHandler->sendResponse($HS_actionResult);
        } else {
            // no action =  reponse and exit
            $HS_responseHandler->sendResponse(array(
                'status' => '404',
                'response' => 'Action unvailable or not specified'
            ));
        }  
    }
 
}

// debug ?
define('HELLOSCAN_DEBUG', false);

// get params from helloscan request
class HelloScan_RequestParams {

    // code from scan result
    public $code = null;

    // action from app
    public $action = null;

    // action from app
    public $authkey = null;

    // qty from app
    public $qty = 1;

    // possible actions
    protected $actions = array(
        'get',
        'add',
        'remove',
    );

    // {{{ getCode()

    /** get code : ean13, reference or id_product
     *
     */
    public function getCode() {
        if(!empty($_GET['code']) && is_numeric($_GET['code'])) {
            return $this->code = (int)$_GET['code'];
        }
        return false;
    }

    // }}}

    // {{{ getQty()

    /** get quantity to change stock
     *
     */
    public function getQty() {
        if(!empty($_GET['qty']) && is_numeric($_GET['qty'])) {
            return $this->qty = (int)$_GET['qty']; 
        } else {
            return $this->qty;
        }
    }

    // }}}

    // {{{ codeExist()

    /** check product code
     *
     */
    public function codeExist() {
        if(!$this->getCode()) {
            return false;
        }
        return true;
    }

    // }}}

    // {{{ getAction()

    /** get action (check, add, remove...)
     *
     */
    public function getAction() {
        if(!empty($_GET['action']) && in_array($_GET['action'], $this->actions)) {
            return $this->action = htmlspecialchars($_GET['action']); 
        }
        return false;
    }

    // }}}

    // {{{ getAuthKey()

    /** get authentification key
     *
     */
    public function getAuthKey() {
        if(!empty($_GET['authkey'])) {
            return $this->authkey = htmlspecialchars($_GET['authkey']); 
        }
        return false;
    }

    // }}}

}

// check autorisation key
class HelloScan_AuthKey {

    // request params
    protected $params = null;

    // {{{ __construct()

    /** constructeur
     *
     * @param object $params request parameters
     */
    public function __construct($params) {
        $this->params = $params;
    }

    // }}}

    // {{{ check()

    /** auth key / compare with saved authkey
     *
     */
    public function check() {

        $params = &JComponentHelper::getParams('com_helloscan');

        if($this->params->getAuthKey() 
            && $params->get('helloscan_authkey')!=''
            && $params->get('helloscan_authkey')==$this->params->getAuthKey()) {
            $this->authkey = $this->params->getAuthKey();
            return true;
        }
        return false;
    }

    // }}}

}
   
// check product code and perform actions
class HelloScan_Check {

    // request params
    protected $params = null;
       
    // product fields rturn format json
    private $return_fields = array(
        'product_id' => 'ID',
        'product_sku' => 'SKU',
        'product_name' => 'Name',
        'product_in_stock' => 'Stock',
        //'product_publish',
        //'product_url',
        //'ship_code_id',
        //'product_price',
    );

    // debug mode
    private $debug = HELLOSCAN_DEBUG;

    // {{{ __construct()

    /** constructeur
     *
     * @param object $params request parameters
     */
    public function __construct($params) {
        $this->params = $params;
    }

    // }}}

    // {{{ checkProductByCode15()

    /** check if code is associate with product => for Joomla 1.5
     *
     */
    public function checkProductByCode15($check_publishstate=false) {

        // virtuemart essantial and ps_product class
        require_once( dirname(__FILE__) . '/../com_virtuemart/virtuemart_parser.php' );
        require_once( CLASSPATH. 'ps_product.php');

        $id_product = null;

        // find product by SKU or id_product
    	$db = new ps_DB();
    	$sql = 'SELECT product_id FROM #__{vm}_product 
                WHERE product_id='.$this->params->getCode().' 
                OR product_sku='.$this->params->getCode();
        // unpublished product ?
		if($check_publishstate) {
			$sql = ' AND product_publish=\'Y\'';
		}
		$db->query($sql);

        // results
        if($db->num_rows()==1 && $results = $db->loadAssocList()) {
            if(!empty($results[0]['product_id'])) {
                $id_product = $results[0]['product_id'];
            }
        }

        // debug
        $this->setDebug('SQL check', $sql);

        // get product
        if(!empty($id_product)) {
            $ps_product = new ps_product();
            if(!$ps_product->product_exists($id_product,false)) {
                return false;
            } else {
                $product = $ps_product->sql($id_product);
                // return and cast
                return (array)$product->record[0];
            }
        } else {
            return false;
        }
        exit;

    }

    // }}}

    // {{{ checkProductByCode17()

    /** check if code is associate with product
     *
     */
    public function checkProductByCode17($check_publishstate=false) {

        // virtuemart configuration
        if (!class_exists( 'VmConfig' )) {
            require(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart'.DS.'helpers'.DS.'config.php');
            VmConfig::loadConfig();
        }

        jimport('joomla.application.component.model');
        JModel::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart' . DS . 'models');

        $ctrl = new JController();
        $ps = $ctrl->getModel('product','VirtuemartModel');

	    //$q = 'SELECT `virtuemart_product_id` FROM `#__virtuemart_product_prices` WHERE `virtuemart_product_id` = "'.$this->_id.'" ';
		$db = JFactory::getDBO();
	    $sql = 'SELECT `virtuemart_product_id` FROM #__virtuemart_products 
                WHERE `virtuemart_product_id`='.$this->params->getCode().'
                OR `product_sku`='.$this->params->getCode();
        // unpublished product ?
		if($check_publishstate) {
			$sql = ' AND `product_publish=\'Y\'';
		}
		$db->setQuery($sql);
        // results
        if($results = $db->loadAssocList()) {
            if(!empty($results[0]['virtuemart_product_id'])) {
                $id_product = $results[0]['virtuemart_product_id'];
            }
        }

        // debug
        $this->setDebug('SQL check', $sql);

        // get product
        if(!empty($id_product)) {
		    $product= $ps->getProduct($id_product);
            if(!is_numeric($product->virtuemart_product_id)) {
                return false;
            } else {
                return (array)$product;
            }
        } else {
            return false;
        }
        exit;

    }

    // }}}

    // {{{ checkProductByCode()

    /** check if code is associate with product
     *
     */
    public function checkProductByCode($check_publishstate=false) {

        // dispatch
        if(version_compare(JVERSION,'1.7.0','ge')) {
            return $this->checkProductByCode17(false);
        } elseif(version_compare(JVERSION,'1.6.0','ge')) {
            return $this->checkProductByCode17(false);
        } else {
            // Joomla! 1.5 code here
            return $this->checkProductByCode15(false);
        }
    
    }

    // }}}

    // {{{ get()

    /** get product infos from code
     *
     * @return array
     */
    public function get() {

        if($product = $this->checkProductByCode()) {
            foreach($product as $k=>$v) {
                if(array_key_exists($k, $this->return_fields)) {
                    $product_tabs[$this->return_fields[$k]] = $v;
                }
            }
            return array(
                'status' => 200,
                'result' => 'Product informations',
                'data' => $product_tabs,
            );
        } else {
           return array(
                'status' => 404,
                'result' => 'No product found',
           );
        }
    }

    // }}}

    // {{{ update_qty()

    /** update stock +/-
     *
     * @return array
     */
    protected function update_qty($action) {
        if($product = $this->checkProductByCode()) {
            if($action=='add') {
                $new_qty = $product['product_in_stock']+intval($this->params->getQty());
            } else {
                $new_qty = $product['product_in_stock']-intval($this->params->getQty());
            }
            // unable to update negative
            if($new_qty<0) {
                return array(
                    'status' => '500',
                    'result' => 'Error during '.$action.' : new quantity could not negative '.$new_qty,
                );
            }
            // dispatch Joomla 1.7 or 1.5
            if(version_compare(JVERSION,'1.7.0','ge') || version_compare(JVERSION,'1.6.0','ge')) {
		        $db = JFactory::getDBO();
                $db->setQuery('UPDATE `#__virtuemart_products` SET `product_in_stock`='.$new_qty.' WHERE `virtuemart_product_id`=' .(int)$product['virtuemart_product_id']);
            } else {

                // virtuemart essantial and ps_product class
                require_once( dirname(__FILE__) . '/../com_virtuemart/virtuemart_parser.php' );
                require_once( CLASSPATH. 'ps_product.php');

                $db = new ps_DB;
                $fields = array('product_in_stock' => $new_qty);
                $db->buildQuery( 'UPDATE', '#__{vm}_product', $fields,  'WHERE product_id='.$product['product_id']);
            }
            if($db->query()) {
                return array(
                    'status' => '200',
                    'result' => 'Quantity updated: '.$action.' '.$this->params->getQty()
                );
            } else {
                return array(
                    'status' => '500',
                    'result' => 'Error during quantity update: '.$action.' '.$this->params->getQty()
                    );
            }
        } else {
           return array(
                'status' => 404,
                'result' => 'No product found to '.$action.' quantity',
           );
        }
    }

    // }}}

    // {{{ add()

    /** add 1 or more product from stock
     *
     * @return array
     */
    public function add() {
        return $this->update_qty('add');
    }

    // }}}

    // {{{ remove()

    /** remove 1 or more product from stock
     *
     * @return array
     */
    public function remove() {
        return $this->update_qty('remove');
    }

    // }}}

    // {{{ execute()

    /** perform action and get result array
     *
     * @return array
     */
    public function execute() {
        return $this->{$this->params->getAction()}();
    }

    // }}}

    // {{{ setDebug()

    /** debug
     *
     * @param string $key Key
     * @param string $value Value debug
     */
    public function setDebug($key,$value) {
        if($this->debug) {
            echo $key.' : '.$value;
        }
    }

}

// response format and send
class HelloScan_ResponseHandler {

    // {{{ sendResponse()

    /** response
     *
     */
    public function sendResponse($response,$format='json') {
        if($format=='json') {
            //header('Content-Type: application/json'); 
            echo json_encode($response);
        }
        exit;
    }

    // }}}

}
