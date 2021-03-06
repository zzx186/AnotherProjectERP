<?php

/**
 * MuWebCloneEngine
 * Created by epmak
 * 22.12.2016
 *
 **/
namespace build\erp\inc;
use mwce\Tools\Configs;
use mwce\db\Connect;
use mwce\Tools\Date;
use mwce\Models\Model;
use mwce\Traits\tInsert;
use mwce\Traits\tUpdate;

class Task extends Model
{
    use tUpdate;
    use tInsert;

    /**
     * список возможных связей задач
     * @var array
     */
    public static $resps = array(
      //  0 =>'Нет',
        1 =>'Окончаие -> Начало',
        2 =>'Начало -> Начало',
        3 =>'Окончание -> Окончание',
    );

    /**
     * @param array $params
     */
    public static function Add($params){
        $db = Connect::start();

        $db->exec("INSERT INTO tbl_tasks (col_taskName,col_StatusID,col_initID,col_respID,col_curatorID,col_pstageID,col_taskDesc,col_createDate,col_startPlan,col_endPlan,col_autoStart,col_taskDur,col_fromPlan,col_nextID,col_bonding) VALUE({$params['col_taskName']},{$params['col_StatusID']},{$params['col_initID']},{$params['col_respID']},{$params['col_curatorID']},{$params['col_pstageID']},{$params['col_taskDesc']},{$params['col_createDate']},{$params['col_startPlan']},{$params['col_endPlan']},{$params['col_autoStart']},{$params['col_taskDur']},{$params['col_fromPlan']},{$params['col_nextID']},{$params['col_bonding']})");

        $db->exec("CALL sp_setTaskPlanQuenue({$params['col_pstageID']},'{$params['col_startPlan']}',{$params['col_nextID']});");
        $db->closeCursor();
    }

    /**
     * возвращает список задач в стадии для создания связи
     * @param int $stageId
     * @param null|int $taskID
     * @return array
     */
    public static function getParentTasks($stageId,$taskID = null){
        $db = Connect::start();
        if(!is_null($taskID))
            $taskID = " AND col_taskID != $taskID";
        else
            $taskID = '';

        $list = array(0=>'Первая задача');
        $q = $db->query("SELECT col_taskID, col_taskName, col_seq FROM tbl_tasks WHERE col_pstageID = $stageId  $taskID");

        while ($r = $q->fetch()){
            $list[$r['col_taskID']] = "{$r['col_seq']}.{$r['col_taskName']}";
        }
        return $list;
    }

    /**
     * возвращает кол-во дней от старта стадии
     * @param int $orderStage
     * @param int $curTaskID
     * @return mixed
     */
    public static function getSumDur($orderStage,$curTaskID = 0){
        $db = Connect::start();

        if($curTaskID >0){
            $curTaskID = "AND tt.col_taskID != $curTaskID
  AND tt.col_seq < (SELECT col_seq FROM tbl_tasks WHERE col_taskID = $curTaskID)";
        }
        else{
            $curTaskID ='';
        }

        $res = $db->query("SELECT 
  COALESCE(SUM(tt.col_taskDur),0) AS totalDur
FROM 
  tbl_tasks tt
WHERE
  tt.col_pstageID = $orderStage $curTaskID AND tt.col_nextID is null")->fetch();

        return $res['totalDur'];
    }

    /**
     * @param null $params
     * @return bool| Task
     */
    public static function getModels($params = null)
    {
        $db = Connect::start();
        $query = self::qBuilder($params);
        if(empty($query))
            return false;

        $q = "SELECT
  tt.*,
  ths.col_StatusName,
  f_getUserFIO(tt.col_initID) AS col_init,
  f_getUserFIO(tt.col_respID) AS col_resp,
  f_getUserFIO(tt.col_curatorID) AS col_curator,
  COALESCE(tt.col_startFact,tt.col_startPlan) AS col_dateStart,
  DATEDIFF(COALESCE(tt.col_endFact,tt.col_endPlan),COALESCE(tt.col_startFact,tt.col_startPlan)) AS col_dayDifs,
  tp.col_projectName,
  tp.col_pnID,
  tp.col_founderID $query
ORDER BY tt.col_endFact DESC, tt.col_endPlan ASC";

        if(isset($params['min'])){
            $q.=" LIMIT ".$params['min'];
            if(!empty($params['max']))
                $q.=" , ".$params['max'];
        }

        return $db->query($q)->fetchAll(static::class);
    }

    /**
     * @param null|array $params
     * @return string
     */
    protected static function qBuilder($params = null){

        $q = 'FROM
  tbl_tasks tt,
  tbl_hb_status ths,
  tbl_project_stage tps,
  tbl_project tp
WHERE
  ths.col_StatusID = tt.col_StatusID
  
  AND tp.col_projectID = tps.col_projectID';

        if(!empty($params['projectID']))
            $q.=" AND tps.col_projectID = ".$params['projectID']." AND tps.col_statusID IN (1,4)";
        else
            $q.=" AND tps.col_pstageID = tt.col_pstageID";

        if(!empty($params['taskName']))
            $q.= " AND tt.col_taskName like '%{$params['taskName']}%'";

        if(isset($params['taskStatus'])){
            if($params['taskStatus']>0)
                $q.= " AND tt.col_StatusID =".$params['taskStatus'];
            else
                $q.= " AND tt.col_StatusID IN (1,4)";
        }


        if(!empty($params['taskInit']))
            $q.= " AND tt.col_initID =".$params['taskInit'];

        if(!empty($params['taskResp']))
            $q.= " AND tt.col_respID =".$params['taskResp'];

        if(!empty($params['taskCurator']))
            $q.= " AND tt.col_curatorID =".$params['taskCurator'];

        if(!empty($params['dbegin'])){
            if($params['taskStatus'] == 5){
                $q.= " AND tt.col_startPlan BETWEEN '{$params['dbegin']} 00:00:00' AND '{$params['dbegin']} 23:59:59'";
            }
            else{
                $q.= " AND tt.col_startFact BETWEEN '{$params['dbegin']} 00:00:00' AND '{$params['dbegin']} 23:59:59'";
            }
        }

        if(!empty($params['endPlan']))
            $q.= " AND tt.col_endPlan BETWEEN '{$params['endPlan']} 00:00:00' AND '{$params['endPlan']} 23:59:59'";

        if(!empty($params['endFact']))
            $q.= " AND tt.col_endFact BETWEEN '{$params['endFact']} 00:00:00' AND '{$params['endFact']} 23:59:59'";

        if(!empty($params['projectName']))
            $q.= " AND tp.col_projectName like '%{$params['projectName']}%'";

        return $q;
    }

    /**
     * @param int $id
     * @return mixed|Task
     */
    public static function getCurModel($id)
    {
        $db = Connect::start();
        return $db->query("SELECT
  tt.*,
  ths.col_StatusName,
  f_getUserFIO(tt.col_initID) AS col_init,
  f_getUserFIO(tt.col_respID) AS col_resp,
  f_getUserFIO(tt.col_curatorID) AS col_curator,
  tps.col_statusID as col_stageStatusID,
  (SELECT ths1.col_StatusName FROM tbl_hb_status ths1 WHERE ths1.col_StatusID = tps.col_statusID) AS col_stageStatusName,
  tps.col_dateCreate AS col_stageDateCreate, 
  tps.col_dateStart as col_stageDateStart, 
  tps.col_dateStartPlan AS col_stageDateStartPlan, 
  tps.col_dateEnd AS col_stageDateEnd, 
  tps.col_dateEndPlan AS col_stageDateEndPlan, 
  tps.col_dateEndFact as col_stageDateEndFact, 
  tp.col_projectName, 
  tp.col_pnID, 
  tp.col_CreateDate as col_projectCreateDate,
  tp.col_projectID
FROM
  tbl_tasks tt,
  tbl_hb_status ths,
  tbl_project_stage tps,
  tbl_project tp
WHERE
  ths.col_StatusID = tt.col_StatusID
  AND tt.col_taskID = $id
  AND tps.col_pstageID = tt.col_pstageID
  AND tp.col_projectID = tps.col_projectID")->fetch(static::class);
    }

    /**
     * согласие с задачей
     */
    public function accept(){
        $this->db->exec("UPDATE tbl_tasks SET col_startFact = NOW(), col_StatusID = 1 WHERE col_taskID = ".$this['col_taskID']);
    }

    /**
     * @param string $text
     * @param bool $isNotice
     */
    public function newComment($text,$isNotice = true){
        $params = [
            'col_taskID'=>$this['col_taskID'],
            'col_UserID'=>Configs::userID(),
            'col_text'=>$text,
        ];

        if($isNotice)
            $params['col_trigger'] = 1;
        else
            $params['col_trigger'] = 0;

        TaskComments::Add($params);
    }

    /**
     * отказ от задачи
     * @param string $reason
     */
    public function decent($reason){
        $this->db->exec("UPDATE tbl_tasks SET col_startFact = NOW(),col_endFact = NOW(), col_failDes='$reason', col_StatusID = 2 WHERE col_taskID = ".$this['col_taskID']);
        self::newComment(htmlspecialchars('<b style="color: red;">Отклонено по причине:</b> ',ENT_QUOTES).$reason,false);
    }

    /**
     * отколонение задачи
     * @param string $reason
     */
    public function fail($reason){
        $this->db->exec("UPDATE tbl_tasks SET col_endFact = NOW(), col_failDes='$reason', col_StatusID = 2 WHERE col_taskID = ".$this['col_taskID']);
        self::newComment(htmlspecialchars('<b style="color: red;">Отклонено по причине:</b> ',ENT_QUOTES).$reason,false);
    }

    public function finish($reason = null){
        if(!is_null($reason))
            $reason = "col_lateFinishDesc='$reason',";
        else
            $reason = '';

        $this->db->exec("UPDATE tbl_tasks SET col_endFact = NOW(), $reason col_StatusID = 3 WHERE col_taskID = ".$this['col_taskID']);
        //задача из плана
        if(!empty($this['col_startPlan'])){
            $childTasks = $this->db->query("SELECT 
  *
from 
  tbl_tasks tt
WHERE
  tt.col_nextID = {$this['col_taskID']}
  AND tt.col_StatusID in (1,5)")->fetchAll();
            if(!empty($childTasks)){
                $pool = '';

                foreach ($childTasks as $childTask) {
                    //завершение вместе
                    if($childTask['col_bonding'] == 3){
                        $pool.=" UPDATE tbl_tasks SET col_endFact = NOW(), col_lateFinishDesc='Завершена из-зи завершения основной задачи' col_StatusID = 3 WHERE col_taskID = {$childTask['col_taskID']} ;";
                    }
                    //завершилася главная, началась зависимая
                    elseif ($childTask['col_bonding'] == 1){
                        $pool.=" UPDATE tbl_tasks SET col_startFact = NOW(), col_StatusID = 1, col_respID = f_checkDeputy(col_respID) WHERE col_taskID = {$childTask['col_taskID']} OR (col_seq = {$childTask['col_seq']} AND col_bonding = 2); ";
                    }
                }

                if(!empty($pool)){
                    $this->db->exec($pool);
                }
            }
        }
    }

    public function edit($params){
        $qString = self::genUpdate($params);
        if(!empty($qString)){
            $this->db->exec("UPDATE tbl_tasks SET $qString WHERE col_taskID = {$this['col_taskID']}");

            if(!empty($this['col_endPlan']) && !empty($this['col_nextID'])){
                $this->db->exec("CALL sp_setTaskPlanQuenue({$this['col_pstageID']},null,{$params['col_nextID']});");
                $this->db->closeCursor();
            }
        }
    }

    public function delete(){
        if($this['col_StatusID'] == 5){
            $this->db->exec("UPDATE tbl_tasks SET col_bonding = 0, col_nextID = null WHERE col_nextID = {$this['col_taskID']}");
            $this->db->exec("DELETE FROM tbl_tasks WHERE col_taskID = {$this['col_taskID']}");
        }
    }

    protected function _adding($name, $value)
    {
        switch ($name){
            case 'col_createDate':
            case 'col_startPlan':
            case 'col_endPlan':
            case 'col_dateStart':
            case 'col_endFact':
            case 'col_startFact':
            case 'col_stageDateCreate':
            case 'col_stageDateStart':
            case 'col_stageDateStartPlan':
            case 'col_stageDateEnd':
            case 'col_stageDateEndPlan':
            case 'col_stageDateEndFact':
            case 'col_projectCreateDate':
                parent::_adding($name.'Legend', Date::transDate($value));
                parent::_adding($name.'LegendTD', Date::transDate($value,true));
                break;
            case 'col_taskDesc':
                if(!empty($value))
                    parent::_adding($name.'Legend', htmlspecialchars_decode($value));
                break;
            case 'col_continueDes':
                    parent::_adding($name.'Legend', '<b class="continueDesNotice">Последняя причина запроса на продления задачи:</b> '.(!empty($value) ? htmlspecialchars_decode($value) : '<i class="noOne">-</i>'));
                break;
            case 'col_failDes':
                    parent::_adding($name.'Legend', '<b class="failDesNotice">Последняя причина отказа от задачи:</b> '. (!empty($value) ? htmlspecialchars_decode($value) : '<i class="noOne">-</i>'));
                break;
            case 'col_lateFinishDesc':
                    parent::_adding($name.'Legend', '<b class="FinishDescNotice">Причина просрочки:</b> '. (!empty($value) ? htmlspecialchars_decode($value) : '<i class="noOne">-</i>'));
                break;
        }
        parent::_adding($name, $value);
    }
}