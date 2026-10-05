<?php
class ControllerExtensionFeedYandexMarket extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/feed/yandex_market');

		$this->document->setTitle($this->language->get('page_title'));

		$this->load->model('export/yandex_market');

		// Ленивая инициализация схемы: на случай деплоя поверх уже установленного
		// расширения, когда install() не вызывается автоматически.
		$this->model_export_yandex_market->installSchema();
		$this->model_export_yandex_market->migrateLegacy();
		$this->model_export_yandex_market->ensureModuleStatus();

		$this->getList();
	}

	public function add() {
		$this->load->language('extension/feed/yandex_market');

		$this->document->setTitle($this->language->get('page_title'));

		$this->load->model('export/yandex_market');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$feed = $this->request->post['feed'];

			$feed['categories'] = isset($feed['categories']) ? implode(',', $feed['categories']) : '';
			$feed['excluded_products'] = $this->cleanProductIds(isset($feed['excluded_products']) ? $feed['excluded_products'] : '');

			$this->model_export_yandex_market->installSchema();

			$this->model_export_yandex_market->addFeed($feed);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/feed/yandex_market', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('extension/feed/yandex_market');

		$this->document->setTitle($this->language->get('page_title'));

		$this->load->model('export/yandex_market');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$feed = $this->request->post['feed'];

			$feed['categories'] = isset($feed['categories']) ? implode(',', $feed['categories']) : '';
			$feed['excluded_products'] = $this->cleanProductIds(isset($feed['excluded_products']) ? $feed['excluded_products'] : '');

			$this->model_export_yandex_market->installSchema();

			$this->model_export_yandex_market->editFeed($this->request->get['feed_id'], $feed);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/feed/yandex_market', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function copy() {
		$this->load->language('extension/feed/yandex_market');

		$this->load->model('export/yandex_market');

		if (isset($this->request->post['selected']) && $this->validate()) {
			foreach ($this->request->post['selected'] as $feed_id) {
				$this->model_export_yandex_market->copyFeed($feed_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/feed/yandex_market', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getList();
	}

	public function delete() {
		$this->load->language('extension/feed/yandex_market');

		$this->load->model('export/yandex_market');

		if (isset($this->request->post['selected']) && $this->validate()) {
			foreach ($this->request->post['selected'] as $feed_id) {
				$this->model_export_yandex_market->deleteFeed($feed_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/feed/yandex_market', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getList();
	}

	public function install() {
		$this->load->model('export/yandex_market');

		$this->model_export_yandex_market->installSchema();
		$this->model_export_yandex_market->migrateLegacy();
		$this->model_export_yandex_market->ensureModuleStatus();
	}

	public function uninstall() {
		// Данные фидов намеренно сохраняются при удалении расширения.
	}

	protected function getList() {
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
			'text'      => $this->language->get('text_home')
		);

		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true),
			'text'      => $this->language->get('text_feed')
		);

		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('extension/feed/yandex_market', 'user_token=' . $this->session->data['user_token'], true),
			'text'      => $this->language->get('page_title')
		);

		$data['add'] = $this->url->link('extension/feed/yandex_market/add', 'user_token=' . $this->session->data['user_token'], true);
		$data['delete'] = $this->url->link('extension/feed/yandex_market/delete', 'user_token=' . $this->session->data['user_token'], true);
		$data['copy'] = $this->url->link('extension/feed/yandex_market/copy', 'user_token=' . $this->session->data['user_token'], true);

		$data['selected'] = array();
		$data['feeds'] = array();

		$results = $this->model_export_yandex_market->getFeeds();

		foreach ($results as $result) {
			$data['feeds'][] = array(
				'feed_id' => $result['feed_id'],
				'name'    => $result['name'],
				'status'  => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'url'     => HTTP_CATALOG . 'index.php?route=extension/feed/yandex_market&feed_id=' . $result['feed_id'],
				'edit'    => $this->url->link('extension/feed/yandex_market/edit', 'user_token=' . $this->session->data['user_token'] . '&feed_id=' . $result['feed_id'], true)
			);
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/feed/yandex_market_list', $data));
	}

	protected function getForm() {
		$this->model_export_yandex_market->installSchema();

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['categories'])) {
			$data['error_categories'] = $this->error['categories'];
		} else {
			$data['error_categories'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
			'text'      => $this->language->get('text_home')
		);

		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true),
			'text'      => $this->language->get('text_feed')
		);

		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('extension/feed/yandex_market', 'user_token=' . $this->session->data['user_token'], true),
			'text'      => $this->language->get('page_title')
		);

		$feed_info = array();

		if (isset($this->request->get['feed_id'])) {
			$feed_info = $this->model_export_yandex_market->getFeed($this->request->get['feed_id']);
		}

		$defaults = array(
			'name'          => '',
			'status'        => 1,
			'shopname'      => '',
			'company'       => '',
			'currency'      => $this->config->get('config_currency'),
			'in_stock'      => 7,
			'out_of_stock'  => 5,
			'image'         => 1,
			'image_size'    => 1,
			'sales_notes'   => '',
			'attributes'    => 0,
			'options'       => 0,
			'description'   => 0,
			'categories'    => array(),
			'excluded_products' => ''
		);

		$data['feed'] = array();

		foreach ($defaults as $key => $default) {
			if (isset($this->request->post['feed'][$key])) {
				$data['feed'][$key] = $this->request->post['feed'][$key];
			} elseif (isset($feed_info[$key])) {
				$data['feed'][$key] = $feed_info[$key];
			} else {
				$data['feed'][$key] = $default;
			}
		}

		if (!is_array($data['feed']['categories'])) {
			$data['feed']['categories'] = ($data['feed']['categories'] != '') ? explode(',', $data['feed']['categories']) : array();
		}

		$data['feed_id'] = isset($this->request->get['feed_id']) ? (int)$this->request->get['feed_id'] : 0;

		if ($data['feed_id']) {
			$data['action'] = $this->url->link('extension/feed/yandex_market/edit', 'user_token=' . $this->session->data['user_token'] . '&feed_id=' . $data['feed_id'], true);
			$data['feed_url'] = HTTP_CATALOG . 'index.php?route=extension/feed/yandex_market&feed_id=' . $data['feed_id'];
		} else {
			$data['action'] = $this->url->link('extension/feed/yandex_market/add', 'user_token=' . $this->session->data['user_token'], true);
			$data['feed_url'] = '';
		}

		$data['cancel'] = $this->url->link('extension/feed/yandex_market', 'user_token=' . $this->session->data['user_token'], true);

		$this->load->model('localisation/stock_status');

		$data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();

		$this->load->model('catalog/category');

		$data['categories'] = $this->model_catalog_category->getCategories();

		$this->load->model('localisation/currency');

		$currencies = $this->model_localisation_currency->getCurrencies();

		$allowed_currencies = array_flip(array('RUR', 'RUB', 'BYR', 'BYN', 'KZT', 'UAH', 'USD', 'EUR'));

		$data['currencies'] = array_intersect_key($currencies, $allowed_currencies);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/feed/yandex_market', $data));
	}

	protected function cleanProductIds($value) {
		// Оставляем только цифры и запятые, убираем пустые элементы.
		$ids = array_filter(array_map('intval', preg_split('/[^0-9]+/', (string)$value)));

		return implode(',', array_unique($ids));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'extension/feed/yandex_market')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!isset($this->request->post['feed']['name']) || (utf8_strlen($this->request->post['feed']['name']) < 1) || (utf8_strlen($this->request->post['feed']['name']) > 255)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (empty($this->request->post['feed']['categories'])) {
			$this->error['categories'] = $this->language->get('error_categories');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/feed/yandex_market')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
}
?>
