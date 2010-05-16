<?php


/**
 * RSS control
 *
 * @author Jan Marek
 * @license MIT
 * @copyright (c) Jan Marek 2009
 *
 * @property string $title
 * @property string $description
 * @property string $link
 * @property array $items
 * @property array $propertyElements
 * @property array $itemElements
 * @property-read ArrayObject $properties
 */
class RssControl extends Control
{

	/** @var array allowed channel elements */
	public $propertyElements = array(
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

		$template->channelProperties = $properties;
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
		return Tools::createDateTime($date)->format(DateTime::RFC822);
	}



	/* ****** callbacks *******************************************************/



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



	/* ****** channel getters & setters ***************************************/

	

	/**
	 * Set channel property
	 * @param string $name
	 * @param mixed $value
	 */
	public function setChannelProperty($name, $value)
	{
		if (!in_array($name, $this->propertyElements)) {
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
	public function getChannelProperty($name)
	{
		if (!in_array($name, $this->propertyElements)) {
			throw new InvalidArgumentException("Element '$name' is not valid!");
		}

		return $this->properties[$name];
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
	 * Set title
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->setChannelProperty("title", $title);
	}



	/**
	 * Get title
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getChannelProperty("title");
	}



	/**
	 * Set description
	 * @param string $description
	 */
	public function setDescription($description)
	{
		$this->setChannelProperty("description", $description);
	}



	/**
	 * Get description
	 * @return string
	 */
	public function getDescription()
	{
		return $this->getChannelProperty("description");
	}



	/**
	 * Set link
	 * @param string $link
	 */
	public function setLink($link)
	{
		$this->setChannelProperty("link", $link);
	}



	/**
	 * Get link
	 * @return string
	 */
	public function getLink()
	{
		return $this->getChannelProperty("link");
	}



	/* ****** items getters & setters *****************************************/



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