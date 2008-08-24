<?php
/**
 * AgaviDebugFilter gathers information for debug purposes
 *
 * @author     Daniel Ancuta <daniel.ancuta@whisnet.pl>
 * @author     Veikko Mäkinen <veikko@veikko.fi>
 * @copyright  Authors
 * @version    0.1
 */
abstract class AdtDebugFilter extends AgaviFilter implements AgaviIActionFilter
{
	protected $log = array();

	public function executeOnce(AgaviFilterChain $filterChain, AgaviExecutionContainer $container)
	{
		$this->context->getLoggerManager()->log(__CLASS__.' executeOnce', 'debug');
		
		//procede to execute	
		$this->execute($filterChain, $container);

		//log global (i.e. not per action) stuff
		$this->log['routes']       = $this->getMatchedRoutes();
		$this->log['request_data'] = array('request_parameters' => $this->getContext()->getRequest()->getRequestData()->getParameters(),
					                             'cookies' => $this->getContext()->getRequest()->getRequestData()->getCookies(),
					      											 'headers' => $this->getContext()->getRequest()->getRequestData()->getHeaders() );
		$this->log['log']          = $this->getLogLines();
		$this->log['database']     = $this->adtGetDatabase();
		$this->log['tm']           = $this->getContext()->getTranslationManager();
	}

	public function execute(AgaviFilterChain $filterChain, AgaviExecutionContainer $container)
	{
		$this->context->getLoggerManager()->log(__CLASS__.' execute', 'debug');

		//procede
		$filterChain->execute($container);

		//now the action has been executed and we'll log what can be logged
		$this->log($container);
	}
	
	abstract protected function render(AgaviExecutionContainer $container);

	protected function log(AgaviExecutionContainer $container)
	{
		//keep this simple for now
		$this->log['actions'][] = array (
			'name'         => $container->getActionName(),
			'module'       => $container->getModuleName(),
			'request_data' => $container->getRequestData(),
			'validation'   => $this->getValidationInfo($container),
			'view'         => $this->getViewInfo($container),
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
		$result = array();
		# Matched routes
		$matchedRoutes = $this->getContext()->getRequest()->getAttribute('matched_routes', 'org.agavi.routing');

		foreach( $matchedRoutes as $matchedRoute ) {
			$result[$matchedRoute] = $this->getContext()->getRouting()->getRoute($matchedRoute);
		}

		return $result;
	}

	/**
	 * Get informations about database
	 *
	 * @since 0.1
	 */
	private function adtGetDatabase() {
		$result = array();
		if ( !AgaviConfig::get('core.use_database') ) {
			return $result;
		}

		$result['class_name'] = get_class($this->container->getContext()->getDatabaseManager()->getDatabase());

		return $result;
	}

	/**
	 * Get information about view for action
	 *
	 * @return array
	 * @since 0.1
	 */
	private function getViewInfo(AgaviExecutionContainer $container) {
		$result = array();

		$outputType = $this->getContext()->getController()->getOutputType( $container->getOutputType()->getName() );

		$result['view_name']           = $container->getViewName();
		$result['output_type']         = $container->getOutputType()->getName();
		$result['default_output_type'] = $this->getContext()->getController()->getOutputType()->getName();
		$result['has_renders']         = $outputType->hasRenderers();
		$result['default_layout_name'] = $outputType->getDefaultLayoutName();

		return $result;
	}

	public function getLogLines()
	{
		return $this->context->getRequest()->getAttribute('log', 'debugtoolbar', array());
	}
	
	public function getValidationInfo(AgaviExecutionContainer $container)
	{
		$vm = $container->getValidationManager();
		$result = array();
		
		$result['has_errors'] = $vm->hasErrors();
		$result['severities'] = array(
			200 => 'SILENT',
			300 => 'NOTICE',
			400 => 'ERROR',
			500 => 'CRITICAL',
		);
		$result['incidents'] = $vm->getIncidents();
		
		return $result;
	}

}
?>
