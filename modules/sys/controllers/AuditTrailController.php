<?php

class AuditTrailController extends Controller {

    public function actionTrack($t = "view") {
        $postdata = file_get_contents("php://input");
        $path     = json_decode($postdata, true);
        $tracking = Setting::get('app.auditTrail') == "Enabled";
        
        ## do not track, if tracking is disabled
        if ($tracking != true) {
            return;
        }

        if ($path['module'] == 'dev' && !in_array($path['ctrl'], ['user', 'role']))
            return;

        if (!empty($path)) {
            AuditTrail::savePageInfo($path);
            switch ($t) {
                case "create":
                    AuditTrail::track("", "create", $path);
                    break;
                case "update":
                    AuditTrail::track("", "update", $path);
                    break;
                case "delete":
                    AuditTrail::track("", "delete", $path);
                    break;
                default:
                    AuditTrail::track("", "view", $path);
                    break;
            }
        }
    }

    public function actionDetail($id) {
        $model = $this->loadModel($id, 'SysAuditTrailDetail');

        $this->renderForm('SysAuditTrailDetail', $model);
    }

    public function actionView($key) {
        $model = $this->loadModel(['key' => $key], 'AuditTrail');
        $this->renderForm('SysAuditTrailIndex', [
            'model' => $model->attributes
        ]);
    }

}