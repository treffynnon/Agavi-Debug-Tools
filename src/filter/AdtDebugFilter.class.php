<?php
/**
 * AgaviDebugFilter gathers information for debug purposes
 *
 * @author    Daniel Ancuta <daniel.ancuta@whisnet.pl>
 * @author    Veikko Mäkinen <veikko@veikko.fi>
 * @copyright Authors
 * @version   0.1
 */
class AdtDebugFilter extends AgaviFilter implements AgaviIActionFilter
{

  protected $log = array();

  /**
   * @var AgaviExecutionContainer
   */
  private $container = null;

  public function executeOnce(AgaviFilterChain $filterChain, AgaviExecutionContainer $container)
  {
    $this->container = $container;

    $this->context->getLoggerManager()->log(__CLASS__.' executeOnce', 'debug');
    $this->execute($filterChain, $container);

    $this->log['routes']       = $this->getMatchedRoutes();
    $this->log['request_data'] = $this->getContext()->getRequest()->getRequestData()->getParameters();
    $this->log['view']         = $this->adtGetView();
    $this->log['log']          = $this->getLogLines();
    $this->log['database']     = $this->adtGetDatabase();
    $this->log['tm']           = $this->getContext()->getTranslationManager();
  }

  public function execute(AgaviFilterChain $filterChain, AgaviExecutionContainer $container)
  {
    $this->context->getLoggerManager()->log(__CLASS__.' execute', 'debug');

    //procede
    $filterChain->execute($container);
    //log what can be logged.
    $this->log($container);
  }

  protected function log(AgaviExecutionContainer $container)
  {
    //keep this simple for now
    $this->log['actions'][] = array (
      'name'         => $container->getActionName(),
      'module'       => $container->getModuleName(),
      'request_data' => $container->getRequestData(),
      'view'         => $this->adtGetView()
    );
  }

  /**
   * Get array with matched routes
   *
   * @return array
   * @since 0.1
   */
  private function getMatchedRoutes() {
    # Array with information about matched routes, name of route is an index of array
    $matchedRoutesInformation = array();
    # Matched routes
    $matchedRoutes = $this->getContext()->getRequest()->getAttribute('matched_routes', 'org.agavi.routing');

    foreach( $matchedRoutes as $matchedRoute ) {
      $matchedRoutesInformation[$matchedRoute] = $this->getContext()->getRouting()->getRoute($matchedRoute);
    }

    return $matchedRoutesInformation;
  }

  /**
   * Get informations about database
   *
   * @since 0.1
   * @todo
   * Integration with Propel logger ( http://propel.phpdb.org/trac/wiki/Users/Documentation/1.3/ConfigureLogging )
   */
  private function adtGetDatabase() {
    $database = array();
    if ( !AgaviConfig::get('core.use_database') ) {
      return $database;
    }

    $database['class_name'] = get_class($this->container->getContext()->getDatabaseManager()->getDatabase());

    return $database;
  }

  /**
   * Get information about view for action
   *
   * @return array
   * @since 0.1
   */
  private function adtGetView() {
    $result = array();

    $outputType = $this->getContext()->getController()->getOutputType( $this->container->getOutputType()->getName() );

    $result['view_name']           = $this->container->getViewName();
    $result['output_type']         = $this->container->getOutputType()->getName();
    $result['default_output_type'] = $this->getContext()->getController()->getOutputType()->getName();
    $result['has_renders']         = $outputType->hasRenderers();
    $result['default_layout_name'] = $outputType->getDefaultLayoutName();

    return $result;
  }

  public function getLogLines()
  {
    return $this->context->getRequest()->getAttribute('log', 'debugtoolbar', array());
  }

}
?>