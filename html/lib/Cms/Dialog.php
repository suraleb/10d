<?php
/**
 * @deprecated this class should be removedin future releases
 */
class Cms_Dialog
{
    static protected $_instance = null;

    /**
     * Dialog types
     *
     * @var string
     */
    const TYPE_INFO    = 'info';
    const TYPE_ERROR   = 'error';
    const TYPE_NOTICE  = 'notice';
    const TYPE_SUCCESS = 'success';

    /**
     * current dialog type
     *
     * @var string
     */
    protected $_type = self::TYPE_ERROR;

    /**
     * Current message
     *
     * @var string
     */
    protected $_msg = '';


    /**
     * Dialog options
     *
     * @var array
     */
    protected $_options = array(
        'json' => false,
        'httpOnly' => false
    );

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->_options['json'] = Cms_Input::getInstance()->isAjax();
    }

    /**
     * Creates dialog according params
     *
     * @param string $message
     * @param string $type (Default: TYPE_INFO)
     * @param array $options
     * @return void
     */
    public function construct($message, $type = self::TYPE_INFO, array $options = array())
    {
        // message and type of the dialog
        $this->setMessage($message)->setType($type);

        // apply options
        foreach ($options as $k=>$v) {
            $this->setOption($k, $v);
        }

        // output
        $this->_render();
    }

    /**
     * Transforms message
     *
     * @param string $message
     * @return Cms_Dialog
     */
    public function setMessage($message)
    {
        $l = Cms_Translate::getInstance();

        $this->_msg = (is_array($message) || $l->isCorrectVariable($message)) ?
            $l->_($message) : $message;

        return $this;
    }

    /**
     * Apply param
     *
     * @param string $n
     * @param mixed $v
     * @return Cms_Dialog
     */
    public function setOption($n, $v)
    {
        $this->_options[$n] = $v;
        return $this;
    }

    /**
     * Type set
     *
     * @param string $type
     * @return Cms_Dialog
     */
    public function setType($type)
    {
        $this->_type = $type;
        return $this;
    }

    /**
     * Displays output
     *
     * @return void
     */
    private function _render()
    {
        // if we are using http status
        // send status and exit
        if ($this->_options['httpOnly']) {
            Cms_Core::sndHeaderCode(
                ($this->_type != self::TYPE_SUCCESS) ? 406 : 200
            );
            Cms_Core::shutdown(true);
        }

        // if this message is for ajax
        // encode it and exit
        if ($this->_options['json']) {
            if ($this->_type != self::TYPE_SUCCESS) {
                Cms_Core::ajaxError($this->_msg);
            }
            Cms_Core::ajaxSuccess($hash = array('msg' => $this->_msg));
        }

        // template
        $tpl = Cms_Template::getInstance();
        $tpl->params = array(
            'type'     => $this->_type,
            'body'     => $this->_msg,
            '_options' => $this->_options
        );

        // layout
        $lyt = Cms_Layout::getInstance();
        $lyt->content = $tpl->render('dialog.phtml');
        echo $lyt->render();

        Cms_Core::shutdown();
    }

    /**
     * Singleton
     *
     * @return Cms_Dialog
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
}
