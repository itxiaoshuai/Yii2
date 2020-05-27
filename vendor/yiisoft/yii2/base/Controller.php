<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * Controller is the base class for classes containing controller logic.
 *
 * For more details and usage information on Controller, see the [guide article on controllers](guide:structure-controllers).
 *
 * @property Module[] $modules All ancestor modules that this controller is located within. This property is
 * read-only.
 * @property string $route The route (module ID, controller ID and action ID) of the current request. This
 * property is read-only.
 * @property string $uniqueId The controller ID that is prefixed with the module ID (if any). This property is
 * read-only.
 * @property View|\yii\web\View $view The view object that can be used to render views or view files.
 * @property string $viewPath The directory containing the view files for this controller.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 *
 *
 * Controller是包含控制器逻辑的类的基类。
 *
 * 有关控制器的更多详细信息和用法信息，请参阅[有关控制器的指南文章]（guide：structure-controllers）。
 *
 * @property Module [] $modules 该控制器位于其中的所有祖先模块。该属性是只读。
 * @property string $route 当前请求的路由（模块ID，控制器ID和操作ID）。这个属性是只读的。
 * @property string $uniqueId 带有模块ID（如果有）的控制器ID。该属性是只读。
 * @property View|\yii\web\View $view 可用于呈现视图或查看文件的视图对象。
 * @property string $viewPath 包含此控制器的视图文件的目录。
 *
 * @作者薛强<qiang.xue@gmail.com>
 * @自2.0起
 */
class Controller extends Component implements ViewContextInterface
{
    /**
     * @event ActionEvent an event raised right before executing a controller action.
     * You may set [[ActionEvent::isValid]] to be false to cancel the action execution.
     *
     * @event ActionEvent在执行控制器动作之前引发的事件。
     * 您可以将[[ActionEvent :: isValid]]设置为false，以取消执行动作
     */
    const EVENT_BEFORE_ACTION = 'beforeAction';
    /**
     * @event ActionEvent an event raised right after executing a controller action.
     * @event ActionEvent执行控制器动作后立即引发的事件。
     */
    const EVENT_AFTER_ACTION = 'afterAction';

    /**
     * @var string the ID of this controller.
     * @var string 此控制器的ID
     */
    public $id;
    /**
     * @var Module the module that this controller belongs to.
     * @var Module 该控制器所属的模块。
     */
    public $module;
    /**
     * @var string the ID of the action that is used when the action ID is not specified
     * in the request. Defaults to 'index'.
     * @var string 未指定操作ID时使用的操作的ID在请求中。默认为“索引”。
     */
    public $defaultAction = 'index';
    /**
     * @var null|string|false the name of the layout to be applied to this controller's views.
     * This property mainly affects the behavior of [[render()]].
     * Defaults to null, meaning the actual layout value should inherit that from [[module]]'s layout value.
     * If false, no layout will be applied.
     *
     * @var null|string|false 要应用于此控制器视图的布局名称。
     * 此属性主要影响[[render（）]]的行为。
     * 默认为null，表示实际布局值应继承[[module]]的布局值。
     * 如果为false，则不会应用任何布局
     */
    public $layout;
    /**
     * @var Action the action that is currently being executed. This property will be set
     * by [[run()]] when it is called by [[Application]] to run an action.
     * @var Action 当前正在执行的动作。该属性将被设置
     * 由[[Application]]调用以运行操作时，由[[run（）]]提供。
     */
    public $action;

    /**
     * @var View the view object that can be used to render views or view files.
     * @var View 可用于呈现视图或查看文件的视图对象。
     */
    private $_view;
    /**
     * @var string the root directory that contains view files for this controller.
     * @var string 包含此控制器的视图文件的根目录。
     */
    private $_viewPath;


    /**
     * @param string $id the ID of this controller.
     * @param Module $module the module that this controller belongs to.
     * @param array $config name-value pairs that will be used to initialize the object properties.
     *
     *
     * @param string $id 此控制器的ID。
     * @param Module $module 该控制器所属的模块。
     * @param array $config 名称-值对，将用于初始化对象属性。
     */
    public function __construct($id, $module, $config = [])
    {
        $this->id = $id;
        $this->module = $module;
        parent::__construct($config);
    }

    /**
     * Declares external actions for the controller.
     *
     * This method is meant to be overwritten to declare external actions for the controller.
     * It should return an array, with array keys being action IDs, and array values the corresponding
     * action class names or action configuration arrays. For example,
     *
     * ```php
     * return [
     *     'action1' => 'app\components\Action1',
     *     'action2' => [
     *         'class' => 'app\components\Action2',
     *         'property1' => 'value1',
     *         'property2' => 'value2',
     *     ],
     * ];
     * ```
     *
     * [[\Yii::createObject()]] will be used later to create the requested action
     * using the configuration provided here.
     *
     *********************************************************************************************
     *
     *声明控制器的外部动作。
     *
     *此方法旨在重写以声明控制器的外部操作。
     *它应该返回一个数组，其中数组键为操作ID，数组值对应动作类名称或动作配置数组。例如，
     *
     * ```php
     * return [
     *     'action1' => 'app\components\Action1',
     *     'action2' => [
     *         'class' => 'app\components\Action2',
     *         'property1' => 'value1',
     *         'property2' => 'value2',
     *     ],
     * ];
     * ```
     *
     * [[\\ Yii :: createObject（）]]将在以后用于创建请求的操作使用此处提供的配置。
     */
    public function actions()
    {
        return [];
    }

    /**
     * Runs an action within this controller with the specified action ID and parameters.
     * If the action ID is empty, the method will use [[defaultAction]].
     * @param string $id the ID of the action to be executed.
     * @param array $params the parameters (name-value pairs) to be passed to the action.
     * @return mixed the result of the action.
     * @throws InvalidRouteException if the requested action ID cannot be resolved into an action successfully.
     * @see createAction()
     *
     * 使用指定的动作ID和参数在此控制器内运行一个动作。
     * 如果操作ID为空，则该方法将使用[[defaultAction]]。
     * @param string $id 要执行的动作的ID。
     * @param array $params 传递给动作的参数（名称-值对）。
     * @return mixed 操作的结果。
     * @throws InvalidRouteException 如果请求的操作ID无法成功解析为一个操作。
     * @see createAction（）
     */
    public function runAction($id, $params = [])
    {
        $action = $this->createAction($id);
        if ($action === null) {
            throw new InvalidRouteException('Unable to resolve the request: ' . $this->getUniqueId() . '/' . $id);
        }

        Yii::debug('Route to run: ' . $action->getUniqueId(), __METHOD__);

        if (Yii::$app->requestedAction === null) {
            Yii::$app->requestedAction = $action;
        }

        $oldAction = $this->action;
        $this->action = $action;

        $modules = [];
        $runAction = true;

        // call beforeAction on modules
        foreach ($this->getModules() as $module) {
            if ($module->beforeAction($action)) {
                array_unshift($modules, $module);
            } else {
                $runAction = false;
                break;
            }
        }

        $result = null;

        if ($runAction && $this->beforeAction($action)) {
            // run the action
            $result = $action->runWithParams($params);

            $result = $this->afterAction($action, $result);

            // call afterAction on modules
            foreach ($modules as $module) {
                /* @var $module Module */
                $result = $module->afterAction($action, $result);
            }
        }

        if ($oldAction !== null) {
            $this->action = $oldAction;
        }

        return $result;
    }

    /**
     * Runs a request specified in terms of a route.
     * The route can be either an ID of an action within this controller or a complete route consisting
     * of module IDs, controller ID and action ID. If the route starts with a slash '/', the parsing of
     * the route will start from the application; otherwise, it will start from the parent module of this controller.
     * @param string $route the route to be handled, e.g., 'view', 'comment/view', '/admin/comment/view'.
     * @param array $params the parameters to be passed to the action.
     * @return mixed the result of the action.
     * @see runAction()
     *
     * 运行根据路线指定的请求。
     * 路由可以是此控制器中操作的ID或包含以下内容的完整路由
     * 模块ID，控制器ID和操作ID。如果路由以斜杠“ /”开头，则解析
     * 路线将从应用程序开始；否则，它将从此控制器的父模块开始。
     * @param string $route 要处理的路线，例如'view'，'comment / view'，'/ admin / comment / view'。
     * @param array $params 传递给动作的参数。
     * @return mixed 操作的结果。
     * @see runAction（）
     */
    public function run($route, $params = [])
    {
        $pos = strpos($route, '/');
        if ($pos === false) {
            return $this->runAction($route, $params);
        } elseif ($pos > 0) {
            return $this->module->runAction($route, $params);
        }

        return Yii::$app->runAction(ltrim($route, '/'), $params);
    }

    /**
     * Binds the parameters to the action.
     * This method is invoked by [[Action]] when it begins to run with the given parameters.
     * @param Action $action the action to be bound with parameters.
     * @param array $params the parameters to be bound to the action.
     * @return array the valid parameters that the action can run with.
     *
     * 将参数绑定到操作。
     * [[Action]]在使用给定参数开始运行时将调用此方法。
     * @param Action $action 要与参数绑定的动作。
     * @param array $params 绑定到动作的参数。
     * @return array 可用于执行操作的有效参数。
     */
    public function bindActionParams($action, $params)
    {
        return [];
    }

    /**
     * Creates an action based on the given action ID.
     * The method first checks if the action ID has been declared in [[actions()]]. If so,
     * it will use the configuration declared there to create the action object.
     * If not, it will look for a controller method whose name is in the format of `actionXyz`
     * where `xyz` is the action ID. If found, an [[InlineAction]] representing that
     * method will be created and returned.
     * @param string $id the action ID.
     * @return Action|null the newly created action instance. Null if the ID doesn't resolve into any action.
     *
     * 根据给定的动作ID创建一个动作。
     * 该方法首先检查动作ID是否已在[[actions（）]]中声明。如果是这样的话，
     * 它将使用在那里声明的配置来创建操作对象。
     * 如果不是，它将寻找名称为`actionXyz`格式的控制器方法。
     * 其中`xyz`是动作ID。如果找到，则表示该内容的[[InlineAction]]
     * 方法将被创建并返回。
     * @param string $id 操作ID。
     * @return Action|null 使新创建的动作实例无效。如果ID无法解析为任何操作，则为Null。
     */
    public function createAction($id)
    {
        if ($id === '') {
            $id = $this->defaultAction;
        }

        $actionMap = $this->actions();
        if (isset($actionMap[$id])) {
            return Yii::createObject($actionMap[$id], [$id, $this]);
        }

        if (preg_match('/^(?:[a-z0-9_]+-)*[a-z0-9_]+$/', $id)) {
            $methodName = 'action' . str_replace(' ', '', ucwords(str_replace('-', ' ', $id)));
            if (method_exists($this, $methodName)) {
                $method = new \ReflectionMethod($this, $methodName);
                if ($method->isPublic() && $method->getName() === $methodName) {
                    return new InlineAction($id, $this, $methodName);
                }
            }
        }

        return null;
    }

    /**
     * This method is invoked right before an action is executed.
     *
     * The method will trigger the [[EVENT_BEFORE_ACTION]] event. The return value of the method
     * will determine whether the action should continue to run.
     *
     * In case the action should not run, the request should be handled inside of the `beforeAction` code
     * by either providing the necessary output or redirecting the request. Otherwise the response will be empty.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function beforeAction($action)
     * {
     *     // your custom code here, if you want the code to run before action filters,
     *     // which are triggered on the [[EVENT_BEFORE_ACTION]] event, e.g. PageCache or AccessControl
     *
     *     if (!parent::beforeAction($action)) {
     *         return false;
     *     }
     *
     *     // other custom code here
     *
     *     return true; // or false to not run the action
     * }
     * ```
     *
     * @param Action $action the action to be executed.
     * @return bool whether the action should continue to run.
     *
     *
     *
     *在执行动作之前立即调用此方法。
     *
     *该方法将触发[[EVENT_BEFORE_ACTION]]事件。方法的返回值将确定操作是否应该继续运行。
     *
     *如果该操作不应该运行，则该请求应在`beforeAction`代码内部处理
     *通过提供必要的输出或重定向请求。否则，响应将为空。
     *
     *如果覆盖此方法，则代码应如下所示：
     *
     * ```php
     * public function beforeAction($action)
     * {
     *     // your custom code here, if you want the code to run before action filters,
     *     // which are triggered on the [[EVENT_BEFORE_ACTION]] event, e.g. PageCache or AccessControl
     *
     *     if (!parent::beforeAction($action)) {
     *         return false;
     *     }
     *
     *     // other custom code here
     *
     *     return true; // or false to not run the action
     * }
     * ```
     *
     * @param Action $action 要执行的动作。
     * @return bool 该动作是否应继续运行。
     */
    public function beforeAction($action)
    {
        $event = new ActionEvent($action);
        $this->trigger(self::EVENT_BEFORE_ACTION, $event);
        return $event->isValid;
    }

    /**
     * This method is invoked right after an action is executed.
     *
     * The method will trigger the [[EVENT_AFTER_ACTION]] event. The return value of the method
     * will be used as the action return value.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function afterAction($action, $result)
     * {
     *     $result = parent::afterAction($action, $result);
     *     // your custom code here
     *     return $result;
     * }
     * ```
     *
     * @param Action $action the action just executed.
     * @param mixed $result the action return result.
     * @return mixed the processed action result.
     *
     *
     *在执行动作后立即调用此方法。
     *
     *该方法将触发[[EVENT_AFTER_ACTION]]事件。方法的返回值
     *将用作操作返回值。
     *
     *如果覆盖此方法，则代码应如下所示：
     *
     * ```php
     * public function afterAction($action, $result)
     * {
     *     $result = parent::afterAction($action, $result);
     *     // your custom code here
     *     return $result;
     * }
     * ```
     *
     * @param Action $action 刚执行的动作。
     * @param mixed $result 操作返回结果。
     * @return mixed 已处理的操作结果。
     */
    public function afterAction($action, $result)
    {
        $event = new ActionEvent($action);
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_ACTION, $event);
        return $event->result;
    }

    /**
     * Returns all ancestor modules of this controller.
     * The first module in the array is the outermost one (i.e., the application instance),
     * while the last is the innermost one.
     * @return Module[] all ancestor modules that this controller is located within.
     *
     * 返回此控制器的所有祖先模块。
     * 数组中的第一个模块是最外层的模块（即应用程序实例），
     * 而最后一个是最里面的一个。
     * @return Module []此控制器位于其中的所有祖先模块。
     */
    public function getModules()
    {
        $modules = [$this->module];
        $module = $this->module;
        while ($module->module !== null) {
            array_unshift($modules, $module->module);
            $module = $module->module;
        }

        return $modules;
    }

    /**
     * Returns the unique ID of the controller.
     * @return string the controller ID that is prefixed with the module ID (if any).
     * 返回控制器的唯一ID。
     * @return string 以模块ID（如果有）为前缀的控制器ID。
     */
    public function getUniqueId()
    {
        return $this->module instanceof Application ? $this->id : $this->module->getUniqueId() . '/' . $this->id;
    }

    /**
     * Returns the route of the current request.
     * @return string the route (module ID, controller ID and action ID) of the current request.
     *
     * 返回当前请求的路由。
     * @return string 当前请求的路由（模块ID，控制器ID和操作ID）
     */
    public function getRoute()
    {
        return $this->action !== null ? $this->action->getUniqueId() : $this->getUniqueId();
    }

    /**
     * Renders a view and applies layout if available.
     *
     * The view to be rendered can be specified in one of the following formats:
     *
     * - [path alias](guide:concept-aliases) (e.g. "@app/views/site/index");
     * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes.
     *   The actual view file will be looked for under the [[Application::viewPath|view path]] of the application.
     * - absolute path within module (e.g. "/site/index"): the view name starts with a single slash.
     *   The actual view file will be looked for under the [[Module::viewPath|view path]] of [[module]].
     * - relative path (e.g. "index"): the actual view file will be looked for under [[viewPath]].
     *
     * To determine which layout should be applied, the following two steps are conducted:
     *
     * 1. In the first step, it determines the layout name and the context module:
     *
     * - If [[layout]] is specified as a string, use it as the layout name and [[module]] as the context module;
     * - If [[layout]] is null, search through all ancestor modules of this controller and find the first
     *   module whose [[Module::layout|layout]] is not null. The layout and the corresponding module
     *   are used as the layout name and the context module, respectively. If such a module is not found
     *   or the corresponding layout is not a string, it will return false, meaning no applicable layout.
     *
     * 2. In the second step, it determines the actual layout file according to the previously found layout name
     *    and context module. The layout name can be:
     *
     * - a [path alias](guide:concept-aliases) (e.g. "@app/views/layouts/main");
     * - an absolute path (e.g. "/main"): the layout name starts with a slash. The actual layout file will be
     *   looked for under the [[Application::layoutPath|layout path]] of the application;
     * - a relative path (e.g. "main"): the actual layout file will be looked for under the
     *   [[Module::layoutPath|layout path]] of the context module.
     *
     * If the layout name does not contain a file extension, it will use the default one `.php`.
     *
     * @param string $view the view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * These parameters will not be available in the layout.
     * @return string the rendering result.
     * @throws InvalidArgumentException if the view file or the layout file does not exist.
     *
     *****************************************************************************************************************************
     *
     * 渲染视图并应用布局（如果可用）。
     *
     * 可以用以下格式之一指定要渲染的视图：
     *
     * -[路径别名]（guide：concept-aliases）（例如“ @ app / views / site / index”）；
     * -应用程序内的绝对路径（例如“ // site / index”）：视图名称以双斜杠开头。
     * 实际的视图文件将在应用程序的[[Application :: viewPath | view path]]下查找。
     * -模块内的绝对路径（例如“ / site / index”）：视图名称以单个斜杠开头。
     * 实际的视图文件将在[[module]]的[[Module :: viewPath | view path]]下查找。
     * -相对路径（例如“索引”）：实际的视图文件将在[[viewPath]]下查找。
     *
     * 要确定应采用哪种布局，请执行以下两个步骤：
     *
     * 1.在第一步中，它确定布局名称和上下文模块：
     *
     * -如果将[[layout]]指定为字符串，请使用它作为布局名称，并使用[[module]]作为上下文模块；
     * -如果[[layout]]为null，则搜索该控制器的所有祖先模块并找到第一个
     * [[Module :: layout | layout]]不为null的模块。布局及相应的模块
     * 分别用作布局名称和上下文模块。如果找不到这样的模块
     * 或对应的布局不是字符串，它将返回false，表示没有适用的布局。
     *
     * 2.在第二步中，根据先前找到的布局名称确定实际的布局文件
     * 和上下文模块。布局名称可以是：
     *
     * -[路径别名]（guide：concept-aliases）（例如“ @ app / views / layouts / main”）；
     * -绝对路径（例如“ / main”）：布局名称以斜杠开头。实际的布局文件将是
     * 在应用程序的[[Application :: layoutPath | layout路径]]下查找；
     * -相对路径（例如“ main”）：实际的布局文件将在
     * 上下文模块的[[Module :: layoutPath | layout路径]]。
     *
     * 如果布局名称不包含文件扩展名，它将使用默认的`.php`。
     *
     * @param string $view 视图名称。
     * @param array $params 应该在视图中可用的参数（名称-值对）。
     * 这些参数在布局中不可用。
     * @return string 呈现结果。
     * @throws InvalidArgumentException 如果视图文件或布局文件不存在。
     */
    public function render($view, $params = [])
    {
        $content = $this->getView()->render($view, $params, $this);
        return $this->renderContent($content);
    }

    /**
     * Renders a static string by applying a layout.
     * @param string $content the static string being rendered
     * @return string the rendering result of the layout with the given static string as the `$content` variable.
     * If the layout is disabled, the string will be returned back.
     * @since 2.0.1
     *
     * 通过应用布局呈现静态字符串。
     * @param string $content 呈现的静态字符串
     * @return string 以给定的静态字符串作为$content变量的布局渲染结果。
     * 如果禁用布局，则字符串将返回。
     * @自2.0.1起
     */
    public function renderContent($content)
    {
        $layoutFile = $this->findLayoutFile($this->getView());
        if ($layoutFile !== false) {
            return $this->getView()->renderFile($layoutFile, ['content' => $content], $this);
        }

        return $content;
    }

    /**
     * Renders a view without applying layout.
     * This method differs from [[render()]] in that it does not apply any layout.
     * @param string $view the view name. Please refer to [[render()]] on how to specify a view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws InvalidArgumentException if the view file does not exist.
     *
     *
     * 渲染视图而不应用布局。
     * 此方法与[[render（）]]的不同之处在于它不应用任何布局。
     * @param string $view 视图名称。有关如何指定视图名称的信息，请参考[[render（）]]。
     * @param array $params 应该在视图中可用的参数（名称-值对）。
     * @return string 呈现结果。
     * @throws InvalidArgumentException 如果视图文件不存在。
     */
    public function renderPartial($view, $params = [])
    {
        return $this->getView()->render($view, $params, $this);
    }

    /**
     * Renders a view file.
     * @param string $file the view file to be rendered. This can be either a file path or a [path alias](guide:concept-aliases).
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws InvalidArgumentException if the view file does not exist.
     *
     * 渲染视图文件。
     * @param string $file 要渲染的视图文件。这可以是文件路径，也可以是[路径别名]（guide：concept-aliases）。
     * @param array $params 应该在视图中可用的参数（名称-值对）。
     * @return string 呈现结果。
     * @throws InvalidArgumentException 如果视图文件不存在。
     */
    public function renderFile($file, $params = [])
    {
        return $this->getView()->renderFile($file, $params, $this);
    }

    /**
     * Returns the view object that can be used to render views or view files.
     * The [[render()]], [[renderPartial()]] and [[renderFile()]] methods will use
     * this view object to implement the actual view rendering.
     * If not set, it will default to the "view" application component.
     * @return View|\yii\web\View the view object that can be used to render views or view files.
     *
     *
     * 返回可用于呈现视图或视图文件的视图对象。
     * [[render（）]]，[[renderPartial（）]]和[[renderFile（）]]方法将使用
     * 此视图对象实现实际的视图渲染。
     * 如果未设置，它将默认为“视图”应用程序组件。
     * @return View|\yii\web\View 可用于呈现视图或查看文件的视图对象。
     */
    public function getView()
    {
        if ($this->_view === null) {
            $this->_view = Yii::$app->getView();
        }

        return $this->_view;
    }

    /**
     * Sets the view object to be used by this controller.
     * @param View|\yii\web\View $view the view object that can be used to render views or view files.
     *
     * 设置此控制器要使用的视图对象。
     * @param View|\yii\web\View $view 可用于呈现视图或查看文件的视图对象。
     */
    public function setView($view)
    {
        $this->_view = $view;
    }

    /**
     * Returns the directory containing view files for this controller.
     * The default implementation returns the directory named as controller [[id]] under the [[module]]'s
     * [[viewPath]] directory.
     * @return string the directory containing the view files for this controller.
     *
     * 返回包含此控制器的视图文件的目录。
     * 默认实现返回[[module]]下的名为控制器[[id]]的目录
     * [[viewPath]]目录。
     * @return string 包含此控制器的视图文件的目录。
     */
    public function getViewPath()
    {
        if ($this->_viewPath === null) {
            $this->_viewPath = $this->module->getViewPath() . DIRECTORY_SEPARATOR . $this->id;
        }

        return $this->_viewPath;
    }

    /**
     * Sets the directory that contains the view files.
     * @param string $path the root directory of view files.
     * @throws InvalidArgumentException if the directory is invalid
     * @since 2.0.7
     *
     * 设置包含视图文件的目录。
     * @param string $path 视图文件的根目录。
     * @throws InvalidArgumentException 如果目录无效
     * @自2.0.7起
     */
    public function setViewPath($path)
    {
        $this->_viewPath = Yii::getAlias($path);
    }

    /**
     * Finds the applicable layout file.
     * @param View $view the view object to render the layout file.
     * @return string|bool the layout file path, or false if layout is not needed.
     * Please refer to [[render()]] on how to specify this parameter.
     * @throws InvalidArgumentException if an invalid path alias is used to specify the layout.
     *
     * 查找适用的布局文件。
     * @param View $view 视图对象以渲染布局文件。
     * @return string | bool 布局文件路径；如果不需要布局，则返回false。
     * 有关如何指定此参数，请参考[[render（）]]。
     * @throws InvalidArgumentException 如果使用无效的路径别名来指定布局。
     */
    public function findLayoutFile($view)
    {
        $module = $this->module;
        if (is_string($this->layout)) {
            $layout = $this->layout;
        } elseif ($this->layout === null) {
            while ($module !== null && $module->layout === null) {
                $module = $module->module;
            }
            if ($module !== null && is_string($module->layout)) {
                $layout = $module->layout;
            }
        }

        if (!isset($layout)) {
            return false;
        }

        if (strncmp($layout, '@', 1) === 0) {
            $file = Yii::getAlias($layout);
        } elseif (strncmp($layout, '/', 1) === 0) {
            $file = Yii::$app->getLayoutPath() . DIRECTORY_SEPARATOR . substr($layout, 1);
        } else {
            $file = $module->getLayoutPath() . DIRECTORY_SEPARATOR . $layout;
        }

        if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
            return $file;
        }
        $path = $file . '.' . $view->defaultExtension;
        if ($view->defaultExtension !== 'php' && !is_file($path)) {
            $path = $file . '.php';
        }

        return $path;
    }
}
