// Blockly Puzzle Logic Constructor JavaScript
// Global variables
let workspace;
let currentGameId = null;
let apiToken = null;
let autosaveInterval;
const API_BASE = 'https://api.michitai.com/api';

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    checkAuthentication();
    initializeBlockly();
    loadGames();
    startAutosave();
});

// Authentication functions
function checkAuthentication() {
    apiToken = localStorage.getItem('api_token');
    if (!apiToken) {
        document.getElementById('authPanel').classList.add('show');
        return false;
    }
    return true;
}

async function login() {
    const email = document.getElementById('authEmail').value;
    const password = document.getElementById('authPassword').value;
    const statusEl = document.getElementById('authStatus');

    if (!email || !password) {
        statusEl.textContent = 'Please enter email and password';
        statusEl.className = 'error';
        return;
    }

    statusEl.innerHTML = '<span class="loading"></span> Logging in...';

    try {
        const response = await fetch(`${API_BASE}/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });

        const result = await response.json();

        if (result.success && result.data.api_token) {
            apiToken = result.data.api_token;
            localStorage.setItem('api_token', apiToken);
            localStorage.setItem('session_token', result.data.session_token);
            
            statusEl.textContent = 'Login successful!';
            statusEl.className = 'success';
            
            document.getElementById('authPanel').classList.remove('show');
            loadGames();
        } else {
            statusEl.textContent = result.message || 'Login failed';
            statusEl.className = 'error';
        }
    } catch (error) {
        statusEl.textContent = 'Login error: ' + error.message;
        statusEl.className = 'error';
    }
}

// Game management functions
async function loadGames() {
    if (!checkAuthentication()) return;

    const selectEl = document.getElementById('gameSelect');
    selectEl.innerHTML = '<option value="">Loading games...</option>';

    try {
        const response = await fetch(`${API_BASE}/game/list`, {
            headers: {
                'X-API-Token': apiToken
            }
        });

        const result = await response.json();

        if (result.success && result.data.games) {
            selectEl.innerHTML = '<option value="">Select a game...</option>';
            
            result.data.games.forEach(game => {
                const option = document.createElement('option');
                option.value = game.id;
                option.textContent = `${game.name} (ID: ${game.id})`;
                selectEl.appendChild(option);
            });

            selectEl.addEventListener('change', function() {
                currentGameId = this.value;
                if (currentGameId) {
                    loadLogic();
                }
            });
        } else {
            selectEl.innerHTML = '<option value="">No games found</option>';
        }
    } catch (error) {
        selectEl.innerHTML = '<option value="">Error loading games</option>';
        console.error('Load games error:', error);
    }
}

// Initialize Blockly workspace
function initializeBlockly() {
    const toolbox = {
        "kind": "categoryToolbox",
        "contents": [
            {
                "kind": "category",
                "name": "Main Puzzles",
                "colour": "#5C81A6",
                "contents": [
                    {
                        "kind": "block",
                        "type": "trigger_event"
                    },
                    {
                        "kind": "block",
                        "type": "timer_set"
                    },
                    {
                        "kind": "block",
                        "type": "timer_modify"
                    }
                ]
            },
            {
                "kind": "category",
                "name": "Data Puzzles",
                "colour": "#5CA65C",
                "contents": [
                    {
                        "kind": "block",
                        "type": "data_get_player"
                    },
                    {
                        "kind": "block",
                        "type": "data_set_player"
                    },
                    {
                        "kind": "block",
                        "type": "data_get_game"
                    },
                    {
                        "kind": "block",
                        "type": "data_set_game"
                    }
                ]
            },
            {
                "kind": "category",
                "name": "Logic",
                "colour": "#5C68A6",
                "contents": [
                    {
                        "kind": "block",
                        "type": "controls_if"
                    },
                    {
                        "kind": "block",
                        "type": "controls_repeat_ext"
                    },
                    {
                        "kind": "block",
                        "type": "controls_whileUntil"
                    },
                    {
                        "kind": "block",
                        "type": "logic_compare"
                    },
                    {
                        "kind": "block",
                        "type": "logic_operation"
                    }
                ]
            },
            {
                "kind": "category",
                "name": "Operators",
                "colour": "#745CA6",
                "contents": [
                    {
                        "kind": "block",
                        "type": "math_arithmetic"
                    },
                    {
                        "kind": "block",
                        "type": "math_number"
                    },
                    {
                        "kind": "block",
                        "type": "text"
                    },
                    {
                        "kind": "block",
                        "type": "text_join"
                    }
                ]
            },
            {
                "kind": "category",
                "name": "Functions",
                "colour": "#A65C81",
                "contents": [
                    {
                        "kind": "block",
                        "type": "function_power"
                    },
                    {
                        "kind": "block",
                        "type": "function_sqrt"
                    },
                    {
                        "kind": "block",
                        "type": "function_random"
                    }
                ]
            },
            {
                "kind": "category",
                "name": "Notifications",
                "colour": "#A6745C",
                "contents": [
                    {
                        "kind": "block",
                        "type": "notification_email"
                    },
                    {
                        "kind": "block",
                        "type": "notification_push"
                    },
                    {
                        "kind": "block",
                        "type": "notification_ingame"
                    }
                ]
            },
            {
                "kind": "category",
                "name": "Matchmaking",
                "colour": "#A65C5C",
                "contents": [
                    {
                        "kind": "block",
                        "type": "matchmaking_create"
                    },
                    {
                        "kind": "block",
                        "type": "matchmaking_find"
                    },
                    {
                        "kind": "block",
                        "type": "matchmaking_leave"
                    }
                ]
            },
            {
                "kind": "category",
                "name": "Room",
                "colour": "#81A65C",
                "contents": [
                    {
                        "kind": "block",
                        "type": "room_create"
                    },
                    {
                        "kind": "block",
                        "type": "room_join"
                    },
                    {
                        "kind": "block",
                        "type": "room_leave"
                    }
                ]
            },
            {
                "kind": "category",
                "name": "Chat",
                "colour": "#68A65C",
                "contents": [
                    {
                        "kind": "block",
                        "type": "chat_write"
                    },
                    {
                        "kind": "block",
                        "type": "chat_read"
                    },
                    {
                        "kind": "block",
                        "type": "chat_edit"
                    }
                ]
            }
        ]
    };

    workspace = Blockly.inject('puzzle-constructor', {
        toolbox: toolbox,
        grid: {
            spacing: 20,
            length: 3,
            colour: '#ccc',
            snap: true
        },
        zoom: {
            controls: true,
            wheel: true,
            startScale: 1.0,
            maxScale: 3,
            minScale: 0.3,
            scaleSpeed: 1.2
        },
        trashcan: true,
        scrollbars: true,
        horizontalLayout: false,
        toolboxPosition: 'start',
        css: true,
        media: 'https://unpkg.com/blockly/media/',
        rtl: false,
        sounds: true,
        oneBasedIndex: true
    });

    // Define custom blocks
    defineCustomBlocks();

    // Add workspace change listener
    workspace.addChangeListener(updateStatus);
}

// Define custom blocks for multiplayer game logic
function defineCustomBlocks() {
    // Trigger Event Block
    Blockly.Blocks['trigger_event'] = {
        init: function() {
            this.appendDummyInput()
                .appendField("Trigger")
                .appendField(new Blockly.FieldTextInput("event_name"), "NAME");
            this.appendValueInput("PARAMS")
                .setCheck(null)
                .appendField("with parameters");
            this.appendStatementInput("DO")
                .setCheck(null)
                .appendField("do");
            this.setColour(230);
            this.setTooltip("Create a trigger event with parameters");
            this.setHelpUrl("");
        }
    };

    // Timer Set Block
    Blockly.Blocks['timer_set'] = {
        init: function() {
            this.appendDummyInput()
                .appendField("Set timer")
                .appendField(new Blockly.FieldTextInput("timer_name"), "NAME");
            this.appendValueInput("VALUE")
                .setCheck("Number")
                .appendField("to");
            this.appendValueInput("MULTIPLIER")
                .setCheck("Number")
                .appendField("multiplier");
            this.setPreviousStatement(true, null);
            this.setNextStatement(true, null);
            this.setColour(160);
            this.setTooltip("Set a timer with value and multiplier");
        }
    };

    // Data Get Player Block
    Blockly.Blocks['data_get_player'] = {
        init: function() {
            this.appendDummyInput()
                .appendField("Get player data")
                .appendField(new Blockly.FieldTextInput("field_name"), "FIELD");
            this.setOutput(true, null);
            this.setColour(120);
            this.setTooltip("Get player data field");
        }
    };

    // Data Set Player Block
    Blockly.Blocks['data_set_player'] = {
        init: function() {
            this.appendDummyInput()
                .appendField("Set player data")
                .appendField(new Blockly.FieldTextInput("field_name"), "FIELD");
            this.appendValueInput("VALUE")
                .setCheck(null)
                .appendField("to");
            this.setPreviousStatement(true, null);
            this.setNextStatement(true, null);
            this.setColour(120);
            this.setTooltip("Set player data field");
        }
    };

    // Add more custom blocks for all categories...
    // (Additional blocks would be defined here for completeness)
}

// Logic management functions
async function saveLogic() {
    if (!currentGameId) {
        alert('Please select a game first');
        return;
    }

    if (!checkAuthentication()) return;

    try {
        const logicData = Blockly.serialization.workspaces.save(workspace);
        
        const response = await fetch(`${API_BASE}/game/saveLogic`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Token': apiToken
            },
            body: JSON.stringify({
                game_id: currentGameId,
                logic: logicData,
                save_version: true,
                version_description: 'Manual save'
            })
        });

        const result = await response.json();

        if (result.success) {
            showAutosaveIndicator('Logic saved successfully!');
            updateLastSaved();
        } else {
            alert('Save failed: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        alert('Save error: ' + error.message);
    }
}

async function loadLogic() {
    if (!currentGameId) {
        alert('Please select a game first');
        return;
    }

    if (!checkAuthentication()) return;

    try {
        const response = await fetch(`${API_BASE}/game/getLogic?game_id=${currentGameId}`, {
            headers: {
                'X-API-Token': apiToken
            }
        });

        const result = await response.json();

        if (result.success && result.data.logic) {
            workspace.clear();
            Blockly.serialization.workspaces.load(result.data.logic, workspace);
            showAutosaveIndicator('Logic loaded successfully!');
            updateStatus();
        } else {
            console.log('No logic found for this game');
        }
    } catch (error) {
        alert('Load error: ' + error.message);
    }
}

async function exportLogic() {
    if (!currentGameId) {
        alert('Please select a game first');
        return;
    }

    if (!checkAuthentication()) return;

    try {
        const response = await fetch(`${API_BASE}/game/exportLogic`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Token': apiToken
            },
            body: JSON.stringify({
                game_id: currentGameId
            })
        });

        if (response.headers.get('content-type').includes('application/json')) {
            const result = await response.json();
            if (!result.success) {
                alert('Export failed: ' + (result.message || 'Unknown error'));
                return;
            }
        }

        // Download the file
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `game_${currentGameId}_logic.json`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        showAutosaveIndicator('Logic exported successfully!');
    } catch (error) {
        alert('Export error: ' + error.message);
    }
}

function importLogic() {
    document.getElementById('fileInput').click();
}

async function handleFileImport(event) {
    const file = event.target.files[0];
    if (!file) return;

    if (!currentGameId) {
        alert('Please select a game first');
        return;
    }

    if (!checkAuthentication()) return;

    try {
        const text = await file.text();
        const importData = JSON.parse(text);

        const response = await fetch(`${API_BASE}/game/importLogic`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Token': apiToken
            },
            body: JSON.stringify({
                game_id: currentGameId,
                logic: importData.logic || importData
            })
        });

        const result = await response.json();

        if (result.success) {
            loadLogic(); // Reload the workspace
            showAutosaveIndicator('Logic imported successfully!');
        } else {
            alert('Import failed: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        alert('Import error: ' + error.message);
    }

    // Reset file input
    event.target.value = '';
}

async function simulateLogic() {
    if (!currentGameId) {
        alert('Please select a game first');
        return;
    }

    if (!checkAuthentication()) return;

    try {
        const logicData = Blockly.serialization.workspaces.save(workspace);
        
        const response = await fetch(`${API_BASE}/game/simulateLogic`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Token': apiToken
            },
            body: JSON.stringify({
                game_id: currentGameId,
                logic: logicData
            })
        });

        const result = await response.json();

        if (result.success) {
            displaySimulationResults(result.data.simulation_result);
            showAutosaveIndicator('Simulation completed!');
        } else {
            alert('Simulation failed: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        alert('Simulation error: ' + error.message);
    }
}

function displaySimulationResults(results) {
    const panel = document.getElementById('simulationPanel');
    const resultsDiv = document.getElementById('simulationResults');
    
    let html = '<ul>';
    html += `<li><strong>Valid:</strong> <span class="${results.valid ? 'success' : 'error'}">${results.valid ? 'Yes' : 'No'}</span></li>`;
    html += `<li><strong>Blocks Count:</strong> ${results.blocks_count}</li>`;
    html += `<li><strong>Categories Used:</strong> ${results.categories_used.join(', ')}</li>`;
    
    if (results.warnings.length > 0) {
        html += '<li><strong>Warnings:</strong><ul>';
        results.warnings.forEach(warning => {
            html += `<li class="warning">${warning}</li>`;
        });
        html += '</ul></li>';
    }
    
    if (results.errors.length > 0) {
        html += '<li><strong>Errors:</strong><ul>';
        results.errors.forEach(error => {
            html += `<li class="error">${error}</li>`;
        });
        html += '</ul></li>';
    }
    
    html += '</ul>';
    resultsDiv.innerHTML = html;
    panel.classList.add('show');
}

// Workspace utility functions
function undoAction() {
    workspace.undo(false);
}

function redoAction() {
    workspace.undo(true);
}

function clearWorkspace() {
    if (confirm('Are you sure you want to clear all blocks? This cannot be undone.')) {
        workspace.clear();
        updateStatus();
    }
}

function updateStatus() {
    const blocks = workspace.getAllBlocks();
    document.getElementById('blocksCount').textContent = blocks.length;
    
    const logicData = Blockly.serialization.workspaces.save(workspace);
    const logicSize = JSON.stringify(logicData).length;
    document.getElementById('logicSize').textContent = (logicSize / 1024).toFixed(1) + ' KB';
}

function updateLastSaved() {
    document.getElementById('lastSaved').textContent = new Date().toLocaleTimeString();
}

function showAutosaveIndicator(message) {
    const indicator = document.getElementById('autosaveIndicator');
    indicator.textContent = message;
    indicator.classList.add('show');
    setTimeout(() => {
        indicator.classList.remove('show');
    }, 3000);
}

// Auto-save functionality
function startAutosave() {
    autosaveInterval = setInterval(async () => {
        if (currentGameId && workspace.getAllBlocks().length > 0) {
            try {
                const logicData = Blockly.serialization.workspaces.save(workspace);
                
                const response = await fetch(`${API_BASE}/game/saveLogic`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Token': apiToken
                    },
                    body: JSON.stringify({
                        game_id: currentGameId,
                        logic: logicData,
                        save_version: false
                    })
                });

                const result = await response.json();
                if (result.success) {
                    showAutosaveIndicator('Auto-saved ✓');
                    updateLastSaved();
                }
            } catch (error) {
                console.error('Auto-save error:', error);
            }
        }
    }, 60000); // Auto-save every 60 seconds
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (autosaveInterval) {
        clearInterval(autosaveInterval);
    }
});
