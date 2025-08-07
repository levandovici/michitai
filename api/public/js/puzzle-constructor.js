/**
 * Puzzle Logic Constructor
 * Drag-and-drop visual programming interface for game logic
 * Supports cross-platform code generation (JavaScript, PHP, C#)
 */

class PuzzleConstructor {
    constructor() {
        this.canvas = document.getElementById('logic-canvas');
        this.elements = [];
        this.currentLanguage = 'javascript';
        this.elementCounter = 0;
        
        this.init();
    }
    
    init() {
        this.setupDragAndDrop();
        this.setupEventListeners();
    }
    
    setupDragAndDrop() {
        // Make puzzle elements draggable
        const puzzleElements = document.querySelectorAll('.puzzle-element');
        puzzleElements.forEach(element => {
            element.draggable = true;
            element.addEventListener('dragstart', (e) => this.handleDragStart(e));
        });
        
        // Setup drop zone
        this.canvas.addEventListener('dragover', (e) => this.handleDragOver(e));
        this.canvas.addEventListener('drop', (e) => this.handleDrop(e));
        this.canvas.addEventListener('dragenter', (e) => this.handleDragEnter(e));
        this.canvas.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        
        // Make canvas elements sortable
        new Sortable(this.canvas, {
            animation: 150,
            ghostClass: 'opacity-50',
            onEnd: () => this.updateElementOrder()
        });
    }
    
    setupEventListeners() {
        document.getElementById('clear-canvas').addEventListener('click', () => this.clearCanvas());
        document.getElementById('generate-code').addEventListener('click', () => this.generateCode());
        
        // Language selection
        document.querySelectorAll('.code-lang-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.selectLanguage(e));
        });
    }
    
    handleDragStart(e) {
        const elementData = {
            type: e.target.dataset.type,
            subtype: e.target.dataset.subtype,
            text: e.target.textContent.trim()
        };
        e.dataTransfer.setData('application/json', JSON.stringify(elementData));
    }
    
    handleDragOver(e) {
        e.preventDefault();
    }
    
    handleDragEnter(e) {
        e.preventDefault();
        this.canvas.classList.add('drag-over');
    }
    
    handleDragLeave(e) {
        if (!this.canvas.contains(e.relatedTarget)) {
            this.canvas.classList.remove('drag-over');
        }
    }
    
    handleDrop(e) {
        e.preventDefault();
        this.canvas.classList.remove('drag-over');
        
        try {
            const elementData = JSON.parse(e.dataTransfer.getData('application/json'));
            this.addElementToCanvas(elementData);
        } catch (error) {
            console.error('Failed to parse dropped element data:', error);
        }
    }
    
    addElementToCanvas(elementData) {
        const elementId = `element-${++this.elementCounter}`;
        const element = this.createElement(elementData, elementId);
        
        // Remove placeholder text if this is the first element
        if (this.elements.length === 0) {
            this.canvas.innerHTML = '';
        }
        
        this.canvas.appendChild(element);
        this.elements.push({
            id: elementId,
            ...elementData,
            parameters: this.getDefaultParameters(elementData)
        });
        
        this.updateElementOrder();
    }
    
    createElement(elementData, elementId) {
        const element = document.createElement('div');
        element.className = 'bg-white border-2 border-gray-300 rounded-lg p-3 mb-2 shadow-sm cursor-move';
        element.id = elementId;
        
        const colorClasses = this.getElementColorClasses(elementData.type);
        element.className += ` ${colorClasses}`;
        
        element.innerHTML = this.getElementHTML(elementData, elementId);
        
        // Add event listeners
        element.querySelector('.remove-element').addEventListener('click', () => this.removeElement(elementId));
        
        // Add parameter input listeners
        element.querySelectorAll('.param-input').forEach(input => {
            input.addEventListener('change', () => this.updateElementParameters(elementId));
        });
        
        return element;
    }
    
    getElementColorClasses(type) {
        const colorMap = {
            logic: 'border-blue-300 bg-blue-50',
            operator: 'border-green-300 bg-green-50',
            function: 'border-yellow-300 bg-yellow-50',
            datatype: 'border-purple-300 bg-purple-50',
            game: 'border-red-300 bg-red-50'
        };
        return colorMap[type] || 'border-gray-300 bg-gray-50';
    }
    
    getElementHTML(elementData, elementId) {
        const baseHTML = `
            <div class="flex justify-between items-start mb-2">
                <h4 class="font-semibold text-sm">${elementData.text}</h4>
                <button class="remove-element text-red-500 hover:text-red-700 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        const parametersHTML = this.getParametersHTML(elementData, elementId);
        return baseHTML + parametersHTML;
    }
    
    getParametersHTML(elementData, elementId) {
        const { type, subtype } = elementData;
        let html = '<div class="space-y-2 text-xs">';
        
        switch (type) {
            case 'logic':
                html += this.getLogicParametersHTML(subtype, elementId);
                break;
            case 'operator':
                html += this.getOperatorParametersHTML(subtype, elementId);
                break;
            case 'function':
                html += this.getFunctionParametersHTML(subtype, elementId);
                break;
            case 'datatype':
                html += this.getDataTypeParametersHTML(subtype, elementId);
                break;
            case 'game':
                html += this.getGameParametersHTML(subtype, elementId);
                break;
        }
        
        html += '</div>';
        return html;
    }
    
    getLogicParametersHTML(subtype, elementId) {
        switch (subtype) {
            case 'if':
            case 'if-else':
                return `
                    <div>
                        <label class="block text-gray-600">Condition:</label>
                        <input type="text" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="condition" placeholder="e.g., x > 5">
                    </div>
                `;
            case 'for':
                return `
                    <div>
                        <label class="block text-gray-600">Variable:</label>
                        <input type="text" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="variable" placeholder="i">
                    </div>
                    <div>
                        <label class="block text-gray-600">Start:</label>
                        <input type="number" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="start" value="0">
                    </div>
                    <div>
                        <label class="block text-gray-600">End:</label>
                        <input type="number" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="end" value="10">
                    </div>
                `;
            case 'while':
                return `
                    <div>
                        <label class="block text-gray-600">Condition:</label>
                        <input type="text" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="condition" placeholder="e.g., x < 100">
                    </div>
                `;
            case 'switch':
                return `
                    <div>
                        <label class="block text-gray-600">Variable:</label>
                        <input type="text" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="variable" placeholder="variable">
                    </div>
                    <div>
                        <label class="block text-gray-600">Cases (comma-separated):</label>
                        <input type="text" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="cases" placeholder="1,2,3">
                    </div>
                `;
            default:
                return '';
        }
    }
    
    getOperatorParametersHTML(subtype, elementId) {
        return `
            <div>
                <label class="block text-gray-600">Left operand:</label>
                <input type="text" class="param-input w-full px-2 py-1 border rounded text-xs" 
                       data-param="left" placeholder="variable or value">
            </div>
            <div>
                <label class="block text-gray-600">Right operand:</label>
                <input type="text" class="param-input w-full px-2 py-1 border rounded text-xs" 
                       data-param="right" placeholder="variable or value">
            </div>
        `;
    }
    
    getFunctionParametersHTML(subtype, elementId) {
        switch (subtype) {
            case 'power':
                return `
                    <div>
                        <label class="block text-gray-600">Base:</label>
                        <input type="text" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="base" placeholder="2">
                    </div>
                    <div>
                        <label class="block text-gray-600">Exponent:</label>
                        <input type="text" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="exponent" placeholder="3">
                    </div>
                `;
            case 'sqrt':
                return `
                    <div>
                        <label class="block text-gray-600">Value:</label>
                        <input type="text" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="value" placeholder="16">
                    </div>
                `;
            case 'random':
                return `
                    <div>
                        <label class="block text-gray-600">Min:</label>
                        <input type="number" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="min" value="0">
                    </div>
                    <div>
                        <label class="block text-gray-600">Max:</label>
                        <input type="number" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="max" value="100">
                    </div>
                `;
            default:
                return '';
        }
    }
    
    getDataTypeParametersHTML(subtype, elementId) {
        return `
            <div>
                <label class="block text-gray-600">Variable name:</label>
                <input type="text" class="param-input w-full px-2 py-1 border rounded text-xs" 
                       data-param="name" placeholder="variableName">
            </div>
            <div>
                <label class="block text-gray-600">Initial value:</label>
                <input type="text" class="param-input w-full px-2 py-1 border rounded text-xs" 
                       data-param="value" placeholder="default value">
            </div>
        `;
    }
    
    getGameParametersHTML(subtype, elementId) {
        switch (subtype) {
            case 'trigger':
                return `
                    <div>
                        <label class="block text-gray-600">Trigger name:</label>
                        <input type="text" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="name" placeholder="onPlayerJoin">
                    </div>
                    <div>
                        <label class="block text-gray-600">Event type:</label>
                        <select class="param-input w-full px-2 py-1 border rounded text-xs" data-param="eventType">
                            <option value="event">Event</option>
                            <option value="timer">Timer</option>
                            <option value="condition">Condition</option>
                            <option value="function">Function</option>
                        </select>
                    </div>
                `;
            case 'timer':
                return `
                    <div>
                        <label class="block text-gray-600">Timer name:</label>
                        <input type="text" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="name" placeholder="gameTimer">
                    </div>
                    <div>
                        <label class="block text-gray-600">Duration (seconds):</label>
                        <input type="number" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="duration" value="60">
                    </div>
                    <div>
                        <label class="block text-gray-600">Multiplier:</label>
                        <input type="number" step="0.1" class="param-input w-full px-2 py-1 border rounded text-xs" 
                               data-param="multiplier" value="1.0">
                    </div>
                `;
            default:
                return '';
        }
    }
    
    getDefaultParameters(elementData) {
        const { type, subtype } = elementData;
        const defaults = {};
        
        // Set default values based on element type
        switch (type) {
            case 'logic':
                if (subtype === 'for') {
                    defaults.variable = 'i';
                    defaults.start = '0';
                    defaults.end = '10';
                }
                break;
            case 'function':
                if (subtype === 'random') {
                    defaults.min = '0';
                    defaults.max = '100';
                }
                break;
            case 'game':
                if (subtype === 'timer') {
                    defaults.duration = '60';
                    defaults.multiplier = '1.0';
                }
                break;
        }
        
        return defaults;
    }
    
    updateElementParameters(elementId) {
        const element = this.elements.find(el => el.id === elementId);
        if (!element) return;
        
        const domElement = document.getElementById(elementId);
        const inputs = domElement.querySelectorAll('.param-input');
        
        inputs.forEach(input => {
            const paramName = input.dataset.param;
            element.parameters[paramName] = input.value;
        });
    }
    
    removeElement(elementId) {
        // Remove from DOM
        const domElement = document.getElementById(elementId);
        if (domElement) {
            domElement.remove();
        }
        
        // Remove from elements array
        this.elements = this.elements.filter(el => el.id !== elementId);
        
        // Show placeholder if no elements left
        if (this.elements.length === 0) {
            this.canvas.innerHTML = '<p class="text-gray-500 text-center">Drag puzzle elements here to build your game logic</p>';
        }
        
        this.updateElementOrder();
    }
    
    updateElementOrder() {
        // Update the order of elements based on DOM order
        const domElements = Array.from(this.canvas.children);
        const orderedElements = [];
        
        domElements.forEach(domEl => {
            const element = this.elements.find(el => el.id === domEl.id);
            if (element) {
                orderedElements.push(element);
            }
        });
        
        this.elements = orderedElements;
    }
    
    clearCanvas() {
        this.canvas.innerHTML = '<p class="text-gray-500 text-center">Drag puzzle elements here to build your game logic</p>';
        this.elements = [];
        this.elementCounter = 0;
    }
    
    selectLanguage(e) {
        // Update button styles
        document.querySelectorAll('.code-lang-btn').forEach(btn => {
            btn.className = 'code-lang-btn bg-gray-600 text-white px-2 py-1 rounded text-xs';
        });
        e.target.className = 'code-lang-btn bg-blue-600 text-white px-2 py-1 rounded text-xs';
        
        this.currentLanguage = e.target.dataset.lang;
        this.generateCode();
    }
    
    generateCode() {
        if (this.elements.length === 0) {
            document.getElementById('code-preview').textContent = '// No elements to generate code from\n// Drag elements to the canvas first';
            return;
        }
        
        let code = '';
        
        switch (this.currentLanguage) {
            case 'javascript':
                code = this.generateJavaScript();
                break;
            case 'php':
                code = this.generatePHP();
                break;
            case 'csharp':
                code = this.generateCSharp();
                break;
        }
        
        document.getElementById('code-preview').textContent = code;
    }
    
    generateJavaScript() {
        let code = '// Generated JavaScript Code\n';
        code += '// Compatible with ES6+ and cross-platform data types\n\n';
        
        // Add variable declarations
        const variables = this.elements.filter(el => el.type === 'datatype');
        if (variables.length > 0) {
            code += '// Variable declarations\n';
            variables.forEach(variable => {
                const name = variable.parameters.name || 'variable';
                const value = this.getJSValue(variable.subtype, variable.parameters.value);
                code += `let ${name} = ${value};\n`;
            });
            code += '\n';
        }
        
        // Add functions
        const functions = this.elements.filter(el => el.type === 'function');
        functions.forEach(func => {
            code += this.generateJSFunction(func) + '\n';
        });
        
        // Add game elements
        const gameElements = this.elements.filter(el => el.type === 'game');
        gameElements.forEach(element => {
            code += this.generateJSGameElement(element) + '\n';
        });
        
        // Add main logic
        const logicElements = this.elements.filter(el => el.type === 'logic');
        logicElements.forEach(element => {
            code += this.generateJSLogic(element) + '\n';
        });
        
        return code;
    }
    
    generatePHP() {
        let code = '<?php\n';
        code += '// Generated PHP Code\n';
        code += '// Compatible with PHP 8.x and cross-platform data types\n\n';
        
        // Add variable declarations
        const variables = this.elements.filter(el => el.type === 'datatype');
        if (variables.length > 0) {
            code += '// Variable declarations\n';
            variables.forEach(variable => {
                const name = variable.parameters.name || 'variable';
                const value = this.getPHPValue(variable.subtype, variable.parameters.value);
                code += `$${name} = ${value};\n`;
            });
            code += '\n';
        }
        
        // Add functions
        const functions = this.elements.filter(el => el.type === 'function');
        functions.forEach(func => {
            code += this.generatePHPFunction(func) + '\n';
        });
        
        // Add game elements
        const gameElements = this.elements.filter(el => el.type === 'game');
        gameElements.forEach(element => {
            code += this.generatePHPGameElement(element) + '\n';
        });
        
        // Add main logic
        const logicElements = this.elements.filter(el => el.type === 'logic');
        logicElements.forEach(element => {
            code += this.generatePHPLogic(element) + '\n';
        });
        
        code += '?>';
        return code;
    }
    
    generateCSharp() {
        let code = 'using System;\n';
        code += 'using System.Collections.Generic;\n\n';
        code += '// Generated C# Code\n';
        code += '// Compatible with .NET and cross-platform data types\n\n';
        code += 'public class GameLogic\n{\n';
        
        // Add variable declarations
        const variables = this.elements.filter(el => el.type === 'datatype');
        if (variables.length > 0) {
            code += '    // Variable declarations\n';
            variables.forEach(variable => {
                const name = variable.parameters.name || 'variable';
                const type = this.getCSharpType(variable.subtype);
                const value = this.getCSharpValue(variable.subtype, variable.parameters.value);
                code += `    private ${type} ${name} = ${value};\n`;
            });
            code += '\n';
        }
        
        code += '    public void Execute()\n    {\n';
        
        // Add main logic
        const logicElements = this.elements.filter(el => el.type === 'logic');
        logicElements.forEach(element => {
            code += this.generateCSharpLogic(element, '        ') + '\n';
        });
        
        code += '    }\n';
        
        // Add functions
        const functions = this.elements.filter(el => el.type === 'function');
        functions.forEach(func => {
            code += this.generateCSharpFunction(func) + '\n';
        });
        
        code += '}';
        return code;
    }
    
    getJSValue(dataType, value) {
        if (!value) value = '';
        
        switch (dataType) {
            case 'Boolean':
                return value.toLowerCase() === 'true' ? 'true' : 'false';
            case 'String':
                return `"${value}"`;
            case 'Array':
                return value ? `[${value}]` : '[]';
            case 'Integer':
            case 'Float':
            case 'Double':
                return value || '0';
            default:
                return value || 'null';
        }
    }
    
    getPHPValue(dataType, value) {
        if (!value) value = '';
        
        switch (dataType) {
            case 'Boolean':
                return value.toLowerCase() === 'true' ? 'true' : 'false';
            case 'String':
                return `"${value}"`;
            case 'Array':
                return value ? `[${value}]` : '[]';
            case 'Integer':
            case 'Float':
            case 'Double':
                return value || '0';
            default:
                return value || 'null';
        }
    }
    
    getCSharpType(dataType) {
        const typeMap = {
            'Boolean': 'bool',
            'Char': 'char',
            'Byte': 'byte',
            'Short': 'short',
            'Integer': 'int',
            'Long': 'long',
            'Float': 'float',
            'Double': 'double',
            'String': 'string',
            'Array': 'List<object>'
        };
        return typeMap[dataType] || 'object';
    }
    
    getCSharpValue(dataType, value) {
        if (!value) value = '';
        
        switch (dataType) {
            case 'Boolean':
                return value.toLowerCase() === 'true' ? 'true' : 'false';
            case 'String':
                return `"${value}"`;
            case 'Array':
                return 'new List<object>()';
            case 'Float':
                return (value || '0') + 'f';
            case 'Double':
                return (value || '0') + 'd';
            default:
                return value || '0';
        }
    }
    
    generateJSFunction(func) {
        const { subtype, parameters } = func;
        
        switch (subtype) {
            case 'power':
                return `Math.pow(${parameters.base || '2'}, ${parameters.exponent || '3'})`;
            case 'sqrt':
                return `Math.sqrt(${parameters.value || '16'})`;
            case 'random':
                return `Math.floor(Math.random() * (${parameters.max || '100'} - ${parameters.min || '0'} + 1)) + ${parameters.min || '0'}`;
            default:
                return `// ${subtype} function`;
        }
    }
    
    generateJSLogic(element) {
        const { subtype, parameters } = element;
        
        switch (subtype) {
            case 'if':
                return `if (${parameters.condition || 'true'}) {\n    // Add your code here\n}`;
            case 'if-else':
                return `if (${parameters.condition || 'true'}) {\n    // Add your code here\n} else {\n    // Add alternative code here\n}`;
            case 'for':
                return `for (let ${parameters.variable || 'i'} = ${parameters.start || '0'}; ${parameters.variable || 'i'} < ${parameters.end || '10'}; ${parameters.variable || 'i'}++) {\n    // Add your code here\n}`;
            case 'while':
                return `while (${parameters.condition || 'true'}) {\n    // Add your code here\n}`;
            case 'switch':
                const cases = (parameters.cases || '1,2,3').split(',');
                let switchCode = `switch (${parameters.variable || 'variable'}) {\n`;
                cases.forEach(caseValue => {
                    switchCode += `    case ${caseValue.trim()}:\n        // Add code for case ${caseValue.trim()}\n        break;\n`;
                });
                switchCode += '    default:\n        // Add default code\n        break;\n}';
                return switchCode;
            default:
                return `// ${subtype} logic`;
        }
    }
    
    getLogicStructure() {
        return {
            elements: this.elements,
            data_types: this.getDataTypeCompatibility(),
            generated_at: new Date().toISOString(),
            version: '1.0'
        };
    }
    
    getDataTypeCompatibility() {
        return {
            'Boolean': { 'javascript': 'boolean', 'php': 'bool', 'csharp': 'bool' },
            'Char': { 'javascript': 'string', 'php': 'string', 'csharp': 'char' },
            'Byte': { 'javascript': 'number', 'php': 'int', 'csharp': 'byte' },
            'Short': { 'javascript': 'number', 'php': 'int', 'csharp': 'short' },
            'Integer': { 'javascript': 'number', 'php': 'int', 'csharp': 'int' },
            'Long': { 'javascript': 'number', 'php': 'int', 'csharp': 'long' },
            'Float': { 'javascript': 'number', 'php': 'float', 'csharp': 'float' },
            'Double': { 'javascript': 'number', 'php': 'float', 'csharp': 'double' },
            'String': { 'javascript': 'string', 'php': 'string', 'csharp': 'string' },
            'Array': { 'javascript': 'array', 'php': 'array', 'csharp': 'array' },
            'Enum': { 'javascript': 'string', 'php': 'string', 'csharp': 'enum' }
        };
    }
}

// Initialize puzzle constructor when document is ready
$(document).ready(() => {
    window.puzzleConstructor = new PuzzleConstructor();
});
