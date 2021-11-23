<?php

declare(strict_types=1);

namespace app\modules\orders\controllers;

use app\modules\orders\models\Order;
use app\modules\orders\models\OrderWithUserDTOFactory;
use app\modules\orders\models\OrderWithUserDTOSeeder;
use app\modules\orders\models\SearchForm;
use app\modules\orders\models\ServiceDTO;
use app\modules\orders\models\ServiceDTOFactory;
use yii\data\Pagination;
use yii\db\Query;
use yii\web\Controller;

/**
 * Default controller for the `orders` module
 *
 * @property-read \app\modules\orders\models\ServiceDTO[] $services
 */
class DefaultController extends Controller
{
    /**
     * Renders the index view for the module
     * @param int|null $status
     * @param int|null $service
     * @param int|null $mode
     * @param string|null $search
     * @param int|null $searchFieldId
     * @param bool $isDownloadRequest
     * @return string
     */
    public function actionIndex(int $status = null, int $service = null, int $mode = null, string $search = null, int $searchFieldId = null): string
    {
        $services = $this->getServices();
        $searchModel = $this->getSearchModel($search, $searchFieldId);
        $ordersQuery = $this->getOrdersQuery($searchModel, $status, $service, $mode, $search, $searchFieldId);

        $pagination = new Pagination(
            [
                'pageSize' => 100,
                'totalCount' => $ordersQuery->count()
            ]
        );

        $arOrders = $ordersQuery
            ->limit($pagination->limit)
            ->offset($pagination->offset)
            ->all();
        $ordersFactory = new OrderWithUserDTOFactory($services);
        $orders = $ordersFactory->fromArrayToArray($arOrders);

        return $this->render('index', [
            'orders' => $orders,
            'pagination' => $pagination,
            'services' => $services,
            'serviceId' => $service,
            'mode' => $mode,
            'status' => $status,
            'totalCountWithoutFilters' => Order::find()->count(),
            'searchModel' => $searchModel
        ]);
    }

    /**
     * @param int|null $status
     * @param int|null $service
     * @param int|null $mode
     * @param string|null $search
     * @param int|null $searchFieldId
     * @return \yii\web\Response|\yii\console\Response
     */
    public function actionDownload(int $status = null, int $service = null, int $mode = null, string $search = null, int $searchFieldId = null): \yii\web\Response|\yii\console\Response
    {
        $searchModel = $this->getSearchModel($search, $searchFieldId);
        $ordersQuery = $this->getOrdersQuery($searchModel, $status, $service, $mode, $search, $searchFieldId);
        $arOrders = $ordersQuery
            ->all();
        $ordersFactory = new OrderWithUserDTOFactory($this->getServices());
        $orders = $ordersFactory->fromArrayToArray($arOrders);

        $seeder = new OrderWithUserDTOSeeder();
        $path = $seeder->toCSVFile($orders, \Yii::getAlias('@webroot') . '/csv/');

        return \Yii::$app->response->sendFile($path);
    }

    private function getOrdersQuery(SearchForm $searchModel, int $status = null, int $service = null, int $mode = null, string $search = null, int $searchFieldId = null):  Query
    {
        $searchDataFilter = [];
        if ($searchModel->isAttributesSet() && $searchModel->validate()) {
            switch ($searchModel->field) {
                case SearchForm::ORDER_FIELD_ID:
                    $searchDataFilter = ['in', 'orders.id', $searchModel->text];
                    break;
                case SearchForm::LINK_FIELD_ID:
                    $searchDataFilter = ['like', 'orders.link', $searchModel->text];
                    break;
                case SearchForm::USER_FIELD_ID:
                    $searchDataFilter = ['like', 'CONCAT(users.first_name, \' \', users.last_name)', $searchModel->text];
                    break;
            }
        }

        return (new Query())
            ->select([
                         'orders.id as order_id',
                         'CONCAT(users.first_name, \' \', users.last_name) AS user',
                         'orders.link as order_link',
                         'orders.quantity as order_quantity',
                         'orders.service_id as order_service_id',
                         'orders.status as order_status',
                         'orders.mode as order_mode',
                         'orders.created_at as order_created_at'
                     ])
            ->from('orders')
            ->filterWhere([
                              'orders.status' => $status,
                              'orders.service_id' => $service,
                              'orders.mode' => $mode
                          ])
            ->andFilterWhere($searchDataFilter)
            ->innerJoin('users', 'users.id = orders.user_id')
            ->orderBy(['orders.id' => SORT_DESC]);
    }

    /**
     * @return ServiceDTO[]
     */
    private function getServices(): array
    {
        $arServices = (new Query())
            ->select([
                         'services.id as service_id',
                         'services.name as service_name',
                         'COUNT(orders.id) AS service_count'
                     ])
            ->from('orders')
            ->innerJoin('services', 'orders.service_id = services.id')
            ->groupBy('service_id')
            ->orderBy('service_count DESC')
            ->all();
        $serviceFactory = new ServiceDTOFactory();
        return $serviceFactory->fromDBArrayToArray($arServices);

    }

    /**
     * @param string|null $search
     * @param int|null $searchFieldId
     * @return SearchForm
     */
    private function getSearchModel(string $search = null, int $searchFieldId = null): SearchForm
    {
        $searchModel = new SearchForm();
        if (\Yii::$app->request->isPost) {
            $searchModel = new SearchForm();
            $searchModel->load(\Yii::$app->request->post());
        } elseif (isset($search, $searchFieldId)) {
            $searchModel = new SearchForm(['text' => $search, 'field' => $searchFieldId]);
        }
        return $searchModel;
    }
}
