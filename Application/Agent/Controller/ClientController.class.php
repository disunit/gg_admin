<?php
/**
 * Created by PhpStorm.
 * client: Cherish
 * Date: 2016/12/22
 * Time: 9:46
 */
namespace Agent\Controller;

use Common\Controller\BaseController;
use Think\Page;

class ClientController extends BaseController
{
    public function clientsGet()
    {

        $admin_client = $this->mongo_db->role_info;
        $stock_type = C('SYSTEM.STOCK_TYPE');
        $this->assign("stock_type", $stock_type);

        if (I('get._id')) {
            $search['_id'] = new \MongoId(I('get._id', null));
            $option = array();
            $query = $admin_client->findOne($search, $option);
            $this->_result['data']['clients'] = $query;
            if (I('get.tab') == 'card') {//充卡页面
                $stock_amount_type = C('SYSTEM.STOCK_AMOUNT_TYPE');
                $query['type_name'] = $stock_type[$query['type']];
                $stock_amount = $query['stock_amount'];
                foreach ($stock_amount as $key => $value) {
                    $stock_amount[$key] = array(
                        'name' => $stock_type[$key],
                        'amount' => $value
                    );
                }
                $this->assign("clients", $query);
                $this->assign("stock_amount", $stock_amount);
                $this->assign("stock_amount_type", $stock_amount_type);
                $html = $this->fetch("Client:card");
                $this->_result['data']['html'] = $html;
                $this->response($this->_result);
            }
        } else {
            $search = array();
            $limit = intval(I('get.limit', C('PAGE_NUM')));
            $skip = (intval(I('get.p', 1)) - 1) * $limit;
            filter_array_element($search);
            filter_array_element($option);

            $cursor = $admin_client->find($search)->limit($limit)->skip($skip);
            $result = iterator_to_array($cursor);

            $count = $admin_client->count($search);
            $page = new Page($count, C('PAGE_NUM'));
            $page = $page->show();

            $this->assign("page", $page);
            $this->assign("clients", $result);
            $this->_result['data']['html'] = $this->fetch("Client:index");

            $this->_result['data']['count'] = $count;
            $this->_result['data']['page'] = $page;
            $this->_result['data']['clients'] = $result;
        }
        $this->response($this->_result);
    }

    public function clientsPut()
    {
        $admin_client = $this->mongo_db->role_info;
        $admin_agent = $this->mongo_db->admin_agent;
        $agent_stock_grant_record = $this->mongo_db->agent_stock_grant_record;
        $agent_stock_grant_record_mmo = $this->mongo_db->agent_stock_grant_record_mmo;

        $search['_id'] = new \MongoId(I('put._id'));
        $search['roleid'] = intval(I('put.roleid'));
        $stock_type = intval(I('put.stock_type'));
        //充卡
        $amount = intval(I('put.amount'));
        if (!check_positive_integer($amount)) {
            $this->response($this->_result, 'json', 400, '房卡数量必须为正整数');
        } else {
            //库存是否充足
            $user = $admin_agent->findOne(array("_id" => $_SESSION[MODULE_NAME . '_admin']['_id']));
            if ($user['stock_amount'][$stock_type] < $amount) {
                $this->response($this->_result, 'json', 400, '房卡库存不足，请前往"库存管理"申请足量房卡');
            }
            //$update['$inc'] = array("stock_amount.{$stock_type}" => $amount);
        }
        $client = $admin_client->findOne($search);
        if ($client) {
            //给代理充卡后要扣除代理相应的库存卡数量
            $admin_agent->update(array("_id" => $_SESSION[MODULE_NAME . '_admin']['_id']),
                array('$inc' => array("stock_amount.{$stock_type}" => -$amount))
            );
            //充卡记录，代理后台使用
            $agent_stock_grant_record->insert(
                array(
                    'from_user' => $_SESSION[MODULE_NAME . '_admin']['username'],
                    'to_user' => $client['roleid'],
                    'nickname' => $client['nickname'],
                    'type' => $stock_type,
                    'amount' => $amount,
                    'date' => time(),
                )
            );
            //充卡记录mmo，游戏内使用
            $agent_stock_grant_record_mmo->insert(
                array(
                    'from_user' => $_SESSION[MODULE_NAME . '_admin']['username'],
                    'to_user' => $client['roleid'],
                    'nickname' => $client['nickname'],
                    'type' => $stock_type,
                    'amount' => $amount,
                    'date' => time(),
                )
            );

            $this->response($this->_result, 'json', 201, '充卡成功');
        } else {
            $this->response($this->_result, 'json', 400, '充卡失败');
        }

    }

    //给代理发放房卡记录
    public function recordGet()
    {
        $stock_type = C('SYSTEM.STOCK_TYPE');
        $agent_stock_grant_record = $this->mongo_db->agent_stock_grant_record;
        $search['from_user'] = $_SESSION[MODULE_NAME . '_admin']['username'];
        $limit = intval(I('get.limit', C('PAGE_NUM')));
        $skip = (intval(I('get.p', 1)) - 1) * $limit;
        $option = array();
        $cursor = $agent_stock_grant_record->find($search, $option)->sort(array('date' => 1))->skip($skip)->limit($limit);
        $result = array();
        foreach ($cursor as $item) {
            $item['date'] = date("Y-m-d H:i:s");
            $item['type_name'] = $stock_type[$item['type']];
            array_push($result, $item);
        }

        $this->assign("record", $result);
        $html = $this->fetch("Client:record");
        $this->_result['data']['html'] = $html;
        $this->_result['data']['record'] = $result;
        $this->response($this->_result);
    }
}