<?php
namespace app\modules\home\controllers;
class CompanyController extends CommonController {
    public function init() {
        parent::init();
        $this->view->params['meta_title'] = \YII::t('common','TitMutantboxLiberators');
        $this->view->params['keyword'] = "mutantbox liberators,liberators mutantbox,mutantbox ltd,mutantbox";
        $this->view->params['description'] = "Information about mutantbox liberators.Enter mutantbox.com to find real time strategy games Information! Play strategy online games with mutantbox !";
    }

    public function actionContact() {
        return $this->render('contact.html', [
        ]);
    }
}
