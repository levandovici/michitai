<?php
/**
 * Test Runner for Blockly Logic API Tests
 * Simple script to execute all Blockly-related tests
 */

echo "🧪 Blockly Logic API Test Runner\n";
echo "================================\n\n";

// Include the test class
require_once __DIR__ . '/BlocklyLogicApiTest.php';

try {
    // Run the tests
    $tester = new BlocklyLogicApiTest();
    $results = $tester->runAllTests();
    
    // Exit with appropriate code
    $allPassed = array_reduce($results, function($carry, $result) {
        return $carry && $result['success'];
    }, true);
    
    exit($allPassed ? 0 : 1);
    
} catch (Exception $e) {
    echo "❌ Test runner failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
