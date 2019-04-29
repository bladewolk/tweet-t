<?php

namespace app\controllers\api\v1;

use app\models\User;
use app\services\TwitterService;
use Yii;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;

class TwitterController extends Controller
{
    /**
     * Filter for checking id and secret
     * @param $action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        $request = Yii::$app->request;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!($request->get('id') && $request->get('secret')))
            throw new \Exception('missing parameter', 422);

        if (strlen($request->get('id')) != 32)
            throw new \Exception('missing parameter', 422);


        $requestData = $request->get();
        unset($requestData['secret']);

        if (base64_encode(implode($requestData)) !== $request->get('secret'))
            throw new \Exception('access denied', 403);

        return parent::beforeAction($action);
    }


    /**
     * @return array
     */
    public function actionGetFeed()
    {
        $usersList = User::find()
            ->select(['user'])
            ->distinct()
            ->asArray()
            ->all();

        $usersList = ArrayHelper::getColumn($usersList, 'user');

        $twitterService = new TwitterService;
        $feed = $twitterService->fetchFeed($usersList, 60 * 60);

        return [
            'feed' => $feed
        ];
    }

    /**
     * @throws \Exception
     */
    public function actionStoreUser()
    {
        $model = new User();
        $model->load(Yii::$app->request->get(), '');

        if (!$model->validate())
            throw new \Exception('missing parameter', 422);

        if (!$model->save())
            throw new \Exception('internal error');
    }

    /**
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDeleteUser()
    {
        $model = new User;
        $model->load(Yii::$app->request->get(), '');

        if (!$model->validate())
            throw new \Exception('missing parameter');

        User::find()
            ->where(['user' => $model->user])
            ->one()
            ->delete();
    }
}