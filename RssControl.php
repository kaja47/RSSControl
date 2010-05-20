<?php


/**
 * RSS control
 *
 * @author Jan Marek
 * @license MIT
 * @copyright (c) 2009 Jan Marek
 * @copyright (c) 2010 Karel Čížek <kaja47@k47.cz>
 *
 * @property string $title
 * @property string $description
 * @property string $link
 * @property string $language
 * @property string $copyright
 * @property string $skipDays
 * @property string $managingEditor
 * @property string $webMaster
 * @property string $pubDate
 * @property string $lastBuildDate
 * @property string $category
 * @property string $generator
 * @property string $docs
 * @property string $ttl
 * @property string $image
 * @property string $rating
 * @property string $textInput
 * @property string $skipHours
 *
 * @property array $items
 * @property-read ArrayObject $properties
 */



class RssControl extends Control
{

	/** @var array allowed channel elements */
	public $channelElements = array(
		'title', 'link', 'description', 'language', 'copyright', 'skipDays',
		'managingEditor', 'webMaster', 'pubDate', 'lastBuildDate', 'category',
		'generator', 'docs', 'ttl', 'image', 'rating', 'textInput', 'skipHours',
	);

	/** @var array allowed item elements */
	public $itemElements = array(
		'title', 'link', 'description', 'author', 'category', 'comments',
		'enclosure', 'guid', 'pubDate', 'source',
	);

	/** @var array */
	public $onPrepareProperties = array();

	/** @var array */
	public $onPrepareItem = array();

	/** @var array */
	public $onCheckItem = array();

	/** @var ArrayObject */
	private $properties;

	/** @var array */
	private $items = array();



	/**
	 * Construct
	 * @param IComponentContainer $parent
	 * @param string $name
	 */
	public function __construct(/*Nette\*/IComponentContainer $parent = NULL, $name = NULL)
	{
		parent::__construct($parent, $name);

		$this->properties = new ArrayObject();

		// set default prepare handlers
		$this->onCheckItem[] = callback($this, "checkItem");
		$this->onCheckItem[] = callback($this, "cleanItem");
	}



	/**
	 * Render control
	 */
	public function render()
	{
		// properties
		$properties = $this->getProperties();
		$this->onPrepareProperties($properties);

		// check
		if (empty($properties["title"]) || empty($properties["description"]) || empty($properties["link"])) {
			throw new InvalidStateException("At least one of mandatory properties title, description or link was not set.");
		}

		// render template
		$template = $this->getTemplate();
		$template->setFile(dirname(__FILE__) . "/template.phtml");

		$template->properties = $properties; // channel properties
		$template->items = $this->items;

		$template->render();
	}



	/**
	 * Convert date to RFC822
	 * @param string|date $date
	 * @return string
	 */
	public static function prepareDate($date)
	{
		$timestamp = Tools::createDateTime($date)->getTimestamp();
		return gmdate('D, d M Y H:i:s', $timestamp) . " GMT";
	}



	/* ****** callbacks *************************************************k*47**/



	/**
	 * Check item
	 * @return ArrayObject
	 */
	public function checkItem(ArrayObject $item)
	{
		// check
		if (empty($item["title"]) && empty($item["description"])) {
			throw new InvalidArgumentException("One of 'title' or 'description' has to be set.");
		}

		// checking for allowed tags
		foreach ($item as $key => $value) {
			if (!in_array($key, $this->itemElements)) {
				throw new InvalidArgumentException("Element '$key' is not valid!");
			}
		}
	}



	/**
	 * Prepare item
	 * @return ArrayObject
	 */
	public function cleanItem(ArrayObject $item)
	{
		// guid & link
		if (empty($item["guid"]) && isset($item["link"])) {
			$item["guid"] = $item["link"];
		}

		if (empty($item["link"]) && isset($item["guid"])) {
			$item["link"] = $item["guid"];
		}

		// pubDate
		if (isset($item["pubDate"])) {
			$item["pubDate"] = self::prepareDate($item["pubDate"]);
		}
	}



	/******* channel properties *****************************************k*47**/



	/**
	 * Set channel property
	 * @param string $name
	 * @param mixed $value
	 */
	public function setChannelProperty($name, $value)
	{
		if (!in_array($name, $this->channelElements)) {
			throw new InvalidArgumentException("Element '$name' is not valid!");
		}

		if ($name === "pubDate" || $name === "lastBuildDate") {
			$value = self::prepareDate($value);
		}

		$this->properties[$name] = $value;
	}



	/**
	 * Get channel property
	 * @param string $name
	 * @return mixed
	 */
	protected function &getChannelProperty($name)
	{
		if (!in_array($name, $this->channelElements)) {
			throw new InvalidArgumentException("Element '$name' is not valid!");
		}

		$p = $this->properties[$name];
		return $p;
	}



	/**
	 * Get properties
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}



	/**
	 * Get property
	 * @param string $name
	 * @param mixed $value
	 * @return array
	 */
	public function __set($name, $value)
	{
		if (in_array($name, $this->channelElements)) {
			$this->setChannelProperty($name, $value);
		} else {
			parent::__set($name, $value);
		}
	}



	/**
	 * Set property
	 * @param string $name
	 * @return mixed
	 */
	public function &__get($name)
	{
		if (in_array($name, $this->channelElements)) {
			return $this->getChannelProperty($name);
		} else {
			return parent::__get($name);
		}
	}



	/* ****** items getters & setters ***********************************k*47**/



	/**
	 * Add item
	 * @param array $item
	 */
	public function addItem($item)
	{
		$item = new ArrayObject((array) $item);

		// callbacks
		$this->onPrepareItem($item);
		$this->onCheckItem($item);

		$this->items[] = (array) $item;
	}



	/**
	 * Add array of items
	 * @param array $items
	 */
	public function addItems(array $items)
	{
		foreach ($items as $item) {
			$this->addItem($item);
		}
	}



	/**
	 * Remove all items
	 */
	public function clearItems()
	{
		$this->items = array();
	}



	/**
	 * Get items
	 * @return array
	 */
	public function getItems()
	{
		return $this->items;
	}



	/**
	 * Set items
	 * @param array $items
	 */
	public function setItems($items)
	{
		$this->clearItems();
		$this->addItems($items);
	}


}