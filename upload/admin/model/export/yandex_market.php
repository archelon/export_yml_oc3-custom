<?php
class ModelExportYandexMarket extends Model {
	public function installSchema() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "feed_yandex_market` (
			`feed_id` int(11) NOT NULL AUTO_INCREMENT,
			`name` varchar(255) NOT NULL DEFAULT '',
			`status` tinyint(1) NOT NULL DEFAULT '1',
			`shopname` varchar(255) NOT NULL DEFAULT '',
			`company` varchar(255) NOT NULL DEFAULT '',
			`currency` varchar(3) NOT NULL DEFAULT 'RUB',
			`in_stock` int(11) NOT NULL DEFAULT '7',
			`out_of_stock` int(11) NOT NULL DEFAULT '5',
			`image` tinyint(1) NOT NULL DEFAULT '1',
			`image_size` tinyint(2) NOT NULL DEFAULT '1',
			`sales_notes` varchar(255) NOT NULL DEFAULT '',
			`attributes` tinyint(1) NOT NULL DEFAULT '0',
			`options` tinyint(1) NOT NULL DEFAULT '0',
			`description` tinyint(1) NOT NULL DEFAULT '0',
			`categories` text NOT NULL,
			`date_added` datetime NOT NULL,
			`date_modified` datetime NOT NULL,
			PRIMARY KEY (`feed_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
	}

	public function migrateLegacy() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "feed_yandex_market`");

		if ((int)$query->row['total'] > 0) {
			return;
		}

		$categories = $this->config->get('feed_yandex_market_categories');

		if ($categories === null || $categories === '') {
			return;
		}

		if (is_array($categories)) {
			$categories = implode(',', $categories);
		}

		$legacy = array(
			'shopname'      => '',
			'company'       => '',
			'currency'      => 'RUB',
			'in_stock'      => 7,
			'out_of_stock'  => 5,
			'image'         => 1,
			'image_size'    => 1,
			'sales_notes'   => '',
			'attributes'    => 0,
			'options'       => 0,
			'description'   => 0
		);

		$data = array();

		foreach ($legacy as $field => $default) {
			$value = $this->config->get('feed_yandex_market_' . $field);

			$data[$field] = ($value === null) ? $default : $value;
		}

		$data['name'] = ($data['shopname'] !== '') ? $data['shopname'] : 'Yandex.Market';
		$data['status'] = 1;
		$data['categories'] = $categories;

		$this->addFeed($data);
	}

	public function getFeeds() {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "feed_yandex_market` ORDER BY `name` ASC");

		return $query->rows;
	}

	public function ensureModuleStatus() {
		$query = $this->db->query("SELECT `setting_id` FROM `" . DB_PREFIX . "setting` WHERE `key` = 'feed_yandex_market_status' AND `store_id` = '0'");

		if (!$query->num_rows) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = '0', `code` = 'feed_yandex_market', `key` = 'feed_yandex_market_status', `value` = '1'");
		}
	}

	public function getFeed($feed_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "feed_yandex_market` WHERE `feed_id` = '" . (int)$feed_id . "'");

		return $query->row;
	}

	public function addFeed($data) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "feed_yandex_market` SET
			`name` = '" . $this->db->escape($data['name']) . "',
			`status` = '" . (isset($data['status']) ? (int)$data['status'] : 0) . "',
			`shopname` = '" . $this->db->escape($data['shopname']) . "',
			`company` = '" . $this->db->escape($data['company']) . "',
			`currency` = '" . $this->db->escape($data['currency']) . "',
			`in_stock` = '" . (int)$data['in_stock'] . "',
			`out_of_stock` = '" . (int)$data['out_of_stock'] . "',
			`image` = '" . (int)$data['image'] . "',
			`image_size` = '" . (int)$data['image_size'] . "',
			`sales_notes` = '" . $this->db->escape($data['sales_notes']) . "',
			`attributes` = '" . (isset($data['attributes']) ? (int)$data['attributes'] : 0) . "',
			`options` = '" . (isset($data['options']) ? (int)$data['options'] : 0) . "',
			`description` = '" . (isset($data['description']) ? (int)$data['description'] : 0) . "',
			`categories` = '" . $this->db->escape($data['categories']) . "',
			`date_added` = NOW(),
			`date_modified` = NOW()");

		return $this->db->getLastId();
	}

	public function editFeed($feed_id, $data) {
		$this->db->query("UPDATE `" . DB_PREFIX . "feed_yandex_market` SET
			`name` = '" . $this->db->escape($data['name']) . "',
			`status` = '" . (isset($data['status']) ? (int)$data['status'] : 0) . "',
			`shopname` = '" . $this->db->escape($data['shopname']) . "',
			`company` = '" . $this->db->escape($data['company']) . "',
			`currency` = '" . $this->db->escape($data['currency']) . "',
			`in_stock` = '" . (int)$data['in_stock'] . "',
			`out_of_stock` = '" . (int)$data['out_of_stock'] . "',
			`image` = '" . (int)$data['image'] . "',
			`image_size` = '" . (int)$data['image_size'] . "',
			`sales_notes` = '" . $this->db->escape($data['sales_notes']) . "',
			`attributes` = '" . (isset($data['attributes']) ? (int)$data['attributes'] : 0) . "',
			`options` = '" . (isset($data['options']) ? (int)$data['options'] : 0) . "',
			`description` = '" . (isset($data['description']) ? (int)$data['description'] : 0) . "',
			`categories` = '" . $this->db->escape($data['categories']) . "',
			`date_modified` = NOW()
			WHERE `feed_id` = '" . (int)$feed_id . "'");
	}

	public function copyFeed($feed_id) {
		$feed_info = $this->getFeed($feed_id);

		if (!$feed_info) {
			return false;
		}

		$feed_info['name'] = $feed_info['name'] . ' (copy)';

		unset($feed_info['feed_id']);
		unset($feed_info['date_added']);
		unset($feed_info['date_modified']);

		return $this->addFeed($feed_info);
	}

	public function deleteFeed($feed_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "feed_yandex_market` WHERE `feed_id` = '" . (int)$feed_id . "'");
	}
}
?>
