<?php
// Study cards arrays
$unityCards = [
    [
        'title' => 'Game Objects',
        'content' => "GameObjects are the fundamental entities in Unity. Every object in your game, like characters or lights, is a GameObject.\n- Use `GameObject.Find('name')` to locate objects.\n- Example: `GameObject player = new GameObject('Player');`"
    ],
    [
        'title' => 'Components',
        'content' => "Components add functionality to GameObjects, like scripts or colliders.\n- Access with `GetComponent<T>()`.\n- Example: `Rigidbody rb = GetComponent<Rigidbody>();`"
    ],
    [
        'title' => 'MonoBehaviour',
        'content' => "MonoBehaviour is the base class for scripts.\n- Key methods: `Start()`, `Update()`.\n- Example: `void Update() { transform.Rotate(0, 1, 0); }`"
    ],
    [
        'title' => 'Transform',
        'content' => "Transform controls position, rotation, and scale.\n- Example: `transform.position = new Vector3(0, 1, 0);`"
    ],
    [
        'title' => 'Vector3',
        'content' => "Vector3 represents 3D vectors.\n- Common use: `Vector3.forward`.\n- Example: `transform.Translate(Vector3.forward * Time.deltaTime);`"
    ],
    [
        'title' => 'Time.deltaTime',
        'content' => "Time.deltaTime ensures frame-rate independent movement.\n- Example: `transform.position += Vector3.up * speed * Time.deltaTime;`"
    ],
    [
        'title' => 'Rigidbody',
        'content' => "Rigidbody enables physics.\n- Add force: `AddForce()`.\n- Example: `rb.AddForce(Vector3.up * 10);`"
    ],
    [
        'title' => 'Collider',
        'content' => "Colliders define an object’s physical shape.\n- Types: Box, Sphere, Capsule.\n- Example: `GetComponent<BoxCollider>().enabled = false;`"
    ],
    [
        'title' => 'OnCollisionEnter',
        'content' => "Called when objects collide.\n- Requires Collider.\n- Example: `void OnCollisionEnter(Collision col) { Debug.Log('Hit!'); }`"
    ],
    [
        'title' => 'OnTriggerEnter',
        'content' => "Called when entering a trigger Collider.\n- Set Collider as trigger.\n- Example: `void OnTriggerEnter(Collider other) { Destroy(other.gameObject); }`"
    ],
    [
        'title' => 'Scene Management',
        'content' => "Load scenes with SceneManager.\n- Example: `SceneManager.LoadScene('Level2');`"
    ],
    [
        'title' => 'Instantiate',
        'content' => "Creates GameObject copies.\n- Example: `Instantiate(prefab, new Vector3(0, 0, 0), Quaternion.identity);`"
    ],
    [
        'title' => 'Destroy',
        'content' => "Removes GameObjects or Components.\n- Example: `Destroy(gameObject);`"
    ],
    [
        'title' => 'Quaternion',
        'content' => "Handles rotations.\n- Use `Quaternion.Euler`.\n- Example: `transform.rotation = Quaternion.Euler(0, 90, 0);`"
    ],
    [
        'title' => 'Camera',
        'content' => "Controls what the player sees.\n- Main Camera: `Camera.main`.\n- Example: `Camera.main.transform.position = new Vector3(0, 10, -10);`"
    ],
    [
        'title' => 'Input System',
        'content' => "Handles user input.\n- Old system: `Input.GetKey()`.\n- Example: `if (Input.GetKey(KeyCode.Space)) { Jump(); }`"
    ],
    [
        'title' => 'UI Text',
        'content' => "Displays text in UI.\n- Requires TextMeshPro.\n- Example: `textMeshPro.text = 'Score: ' + score;`"
    ],
    [
        'title' => 'Canvas',
        'content' => "Container for UI elements.\n- Set render mode: World Space, Screen Space.\n- Example: `canvas.GetComponent<Canvas>().worldCamera = Camera.main;`"
    ],
    [
        'title' => 'Animator',
        'content' => "Controls animations.\n- Use Animator Controller.\n- Example: `animator.SetBool('isRunning', true);`"
    ],
    [
        'title' => 'Animation Clip',
        'content' => "Stores animation data.\n- Add to Animator.\n- Example: `animator.Play('Jump');`"
    ],
    [
        'title' => 'Particle System',
        'content' => "Creates visual effects like fire.\n- Example: `particleSystem.Play();`"
    ],
    [
        'title' => 'Audio Source',
        'content' => "Plays sound clips.\n- Example: `audioSource.PlayOneShot(clip);`"
    ],
    [
        'title' => 'Physics.Raycast',
        'content' => "Shoots a ray to detect hits.\n- Example: `if (Physics.Raycast(transform.position, Vector3.forward, out hit)) { Debug.Log(hit.collider.name); }`"
    ],
    [
        'title' => 'Coroutines',
        'content' => "Run code over time.\n- Use `yield return`.\n- Example: `IEnumerator Wait() { yield return new WaitForSeconds(2); Debug.Log('Done'); }`"
    ],
    [
        'title' => 'ScriptableObject',
        'content' => "Stores data independent of scenes.\n- Example: `CreateAssetMenu`\n- Example: `public class Item : ScriptableObject { public string itemName; }`"
    ],
    [
        'title' => 'Prefab',
        'content' => "Reusable GameObject template.\n- Example: `GameObject instance = Instantiate(prefab);`"
    ],
    [
        'title' => 'NavMesh',
        'content' => "Enables AI pathfinding.\n- Bake NavMesh in Scene.\n- Example: `navMeshAgent.SetDestination(target.position);`"
    ],
    [
        'title' => 'NavMeshAgent',
        'content' => "Controls AI movement.\n- Example: `agent.speed = 5;`"
    ],
    [
        'title' => 'Shader',
        'content' => "Defines material appearance.\n- Use Shader Graph.\n- Example: `material.shader = Shader.Find('Standard');`"
    ],
    [
        'title' => 'Material',
        'content' => "Defines how surfaces look.\n- Example: `renderer.material.color = Color.red;`"
    ],
    [
        'title' => 'Light',
        'content' => "Illuminates scenes.\n- Types: Directional, Point.\n- Example: `light.intensity = 2;`"
    ],
    [
        'title' => 'Skybox',
        'content' => "Background for scenes.\n- Set in Lighting settings.\n- Example: `RenderSettings.skybox = skyboxMaterial;`"
    ],
    [
        'title' => 'Terrain',
        'content' => "Creates landscapes.\n- Add trees, grass.\n- Example: `terrain.terrainData.SetHeights(0, 0, heights);`"
    ],
    [
        'title' => 'Physics Material',
        'content' => "Controls surface properties.\n- Example: `collider.material = new PhysicMaterial('Bouncy');`"
    ],
    [
        'title' => 'PlayerPrefs',
        'content' => "Saves simple data.\n- Example: `PlayerPrefs.SetInt('HighScore', 100);`"
    ],
    [
        'title' => 'SerializeField',
        'content' => "Exposes private fields in Inspector.\n- Example: `[SerializeField] private int health = 100;`"
    ],
    [
        'title' => 'Update vs FixedUpdate',
        'content' => "`Update` runs per frame, `FixedUpdate` for physics.\n- Example: `void FixedUpdate() { rb.AddForce(Vector3.up); }`"
    ],
    [
        'title' => 'LayerMask',
        'content' => "Filters GameObjects by layer.\n- Example: `LayerMask mask = LayerMask.GetMask('Enemy');`"
    ],
    [
        'title' => 'Tag',
        'content' => "Identifies GameObjects.\n- Example: `if (gameObject.CompareTag('Player')) { Debug.Log('Found Player'); }`"
    ],
    [
        'title' => 'EventSystem',
        'content' => "Handles UI input.\n- Required for buttons.\n- Example: `eventSystem.SetSelectedGameObject(button);`"
    ],
    [
        'title' => 'Button',
        'content' => "Triggers actions in UI.\n- Example: `button.onClick.AddListener(() => Debug.Log('Clicked'));`"
    ],
    [
        'title' => 'Image',
        'content' => "Displays sprites in UI.\n- Example: `image.sprite = mySprite;`"
    ],
    [
        'title' => 'Sprite Renderer',
        'content' => "Renders 2D sprites.\n- Example: `spriteRenderer.flipX = true;`"
    ],
    [
        'title' => 'Sorting Layer',
        'content' => "Controls 2D render order.\n- Example: `spriteRenderer.sortingLayerName = 'Foreground';`"
    ],
    [
        'title' => 'Tilemap',
        'content' => "Creates 2D grids.\n- Example: `tilemap.SetTile(new Vector3Int(0, 0, 0), tile);`"
    ],
    [
        'title' => 'Cinemachine',
        'content' => "Advanced camera control.\n- Example: `CinemachineVirtualCamera vcam = GetComponent<CinemachineVirtualCamera>();`"
    ],
    [
        'title' => 'Post-Processing',
        'content' => "Enhances visuals.\n- Requires PostProcessVolume.\n- Example: `volume.profile.Add<Bloom>();`"
    ],
    [
        'title' => 'AssetBundle',
        'content' => "Packages assets for loading.\n- Example: `AssetBundle.LoadFromFile('path');`"
    ],
    [
        'title' => 'Addressables',
        'content' => "Manages assets dynamically.\n- Example: `Addressables.LoadAssetAsync<GameObject>('key');`"
    ],
    [
        'title' => 'Job System',
        'content' => "Optimizes performance.\n- Example: `NativeArray<float> data = new NativeArray<float>(100, Allocator.TempJob);`"
    ],
    [
        'title' => 'Burst Compiler',
        'content' => "Speeds up code.\n- Use with Job System.\n- Example: `[BurstCompile]`"
    ],
    [
        'title' => 'ECS',
        'content' => "Entity Component System for performance.\n- Example: `EntityManager.CreateEntity(typeof(Position));`"
    ],
    [
        'title' => 'DOTS',
        'content' => "Data-Oriented Tech Stack.\n- Includes ECS, Jobs.\n- Example: `SystemBase` for systems."
    ],
    [
        'title' => 'Profiler',
        'content' => "Analyzes performance.\n- Open in Window > Analysis.\n- Example: Check CPU usage."
    ],
    [
        'title' => 'Debug.Log',
        'content' => "Prints to Console.\n- Example: `Debug.Log('Player moved');`"
    ],
    [
        'title' => 'Gizmos',
        'content' => "Visualizes debug info.\n- Example: `void OnDrawGizmos() { Gizmos.DrawSphere(transform.position, 1); }`"
    ],
    [
        'title' => 'Editor Window',
        'content' => "Customizes Unity Editor.\n- Example: `EditorWindow.GetWindow<MyWindow>();`"
    ],
    [
        'title' => 'Attribute',
        'content' => "Modifies Inspector.\n- Example: `[Range(0, 100)] public int health;`"
    ],
    [
        'title' => 'RequireComponent',
        'content' => "Ensures Component exists.\n- Example: `[RequireComponent(typeof(Rigidbody))]`"
    ],
    [
        'title' => 'Custom Inspector',
        'content' => "Customizes script UI.\n- Example: `CustomEditor(typeof(MyScript))`"
    ],
    [
        'title' => 'Scene View',
        'content' => "Visualizes GameObjects.\n- Use Gizmos for debug.\n- Example: `SceneView.RepaintAll();`"
    ],
    [
        'title' => 'Hierarchy',
        'content' => "Organizes GameObjects.\n- Parent/child relationships.\n- Example: `transform.SetParent(parent);`"
    ],
    [
        'title' => 'Project Window',
        'content' => "Manages assets.\n- Example: Drag prefabs to Scene."
    ],
    [
        'title' => 'Inspector',
        'content' => "Edits Component properties.\n- Example: Adjust Transform values."
    ],
    [
        'title' => 'Build Settings',
        'content' => "Configures game export.\n- Example: Add scenes to build."
    ],
    [
        'title' => 'Player Settings',
        'content' => "Defines app properties.\n- Example: Set icon, resolution."
    ],
    [
        'title' => 'Asset Store',
        'content' => "Source for assets.\n- Example: Import packages."
    ],
    [
        'title' => 'Package Manager',
        'content' => "Manages dependencies.\n- Example: Install TextMeshPro."
    ],
    [
        'title' => 'Timeline',
        'content' => "Creates cutscenes.\n- Example: Add Animation Track."
    ],
    [
        'title' => 'Playable Director',
        'content' => "Controls Timeline.\n- Example: `playableDirector.Play();`"
    ],
    [
        'title' => 'Input Action',
        'content' => "New Input System.\n- Example: `action.performed += ctx => Jump();`"
    ],
    [
        'title' => 'XR Interaction',
        'content' => "VR/AR interactions.\n- Example: `XRGrabInteractable`"
    ],
    [
        'title' => 'Shader Graph',
        'content' => "Visual shader creation.\n- Example: Create PBR Graph."
    ],
    [
        'title' => 'VFX Graph',
        'content' => "Advanced particle effects.\n- Example: Create Visual Effect asset."
    ],
    [
        'title' => 'URP',
        'content' => "Universal Render Pipeline.\n- Example: Set URP in Graphics settings."
    ],
    [
        'title' => 'HDRP',
        'content' => "High Definition Render Pipeline.\n- Example: Use for high-end visuals."
    ],
    [
        'title' => 'Lightmap',
        'content' => "Baked lighting.\n- Example: Bake in Lighting window."
    ],
    [
        'title' => 'Probe Volume',
        'content' => "Dynamic lighting.\n- Example: Use with HDRP."
    ],
    [
        'title' => 'Occlusion Culling',
        'content' => "Optimizes rendering.\n- Example: Bake in Occlusion window."
    ],
    [
        'title' => 'LOD Group',
        'content' => "Level of Detail.\n- Example: Add LOD levels to mesh."
    ],
    [
        'title' => 'Reflection Probe',
        'content' => "Real-time reflections.\n- Example: Place in scene."
    ],
    [
        'title' => 'Audio Mixer',
        'content' => "Manages sound levels.\n- Example: Add effects like reverb."
    ],
    [
        'title' => 'Animation Event',
        'content' => "Triggers code in animations.\n- Example: Add event in Animation window."
    ],
    [
        'title' => 'Blend Tree',
        'content' => "Smooths animation transitions.\n- Example: Create in Animator."
    ],
    [
        'title' => 'State Machine',
        'content' => "Controls Animator logic.\n- Example: Add states in Animator."
    ],
    [
        'title' => 'Particle Collision',
        'content' => "Handles particle hits.\n- Example: `void OnParticleCollision(GameObject other) {}`"
    ],
    [
        'title' => 'Trail Renderer',
        'content' => "Creates trails.\n- Example: Adjust width curve."
    ],
    [
        'title' => 'Line Renderer',
        'content' => "Draws lines.\n- Example: `lineRenderer.SetPosition(0, Vector3.zero);`"
    ],
    [
        'title' => 'Cloth',
        'content' => "Simulates fabric.\n- Example: Add Cloth Component."
    ],
    [
        'title' => 'Wheel Collider',
        'content' => "For vehicle physics.\n- Example: `wheelCollider.motorTorque = 100;`"
    ],
    [
        'title' => 'Joint',
        'content' => "Connects Rigidbodies.\n- Types: Hinge, Spring.\n- Example: `hingeJoint.breakForce = 1000;`"
    ],
    [
        'title' => 'Constant Force',
        'content' => "Applies steady force.\n- Example: `constantForce.force = Vector3.up * 5;`"
    ],
    [
        'title' => 'Area Effector',
        'content' => "2D physics force.\n- Example: Add to Collider2D."
    ],
    [
        'title' => 'Sprite Atlas',
        'content' => "Optimizes sprite rendering.\n- Example: Create in Project."
    ],
    [
        'title' => 'Dynamic Batching',
        'content' => "Reduces draw calls.\n- Example: Use same material."
    ],
    [
        'title' => 'Static Batching',
        'content' => "Combines static objects.\n- Example: Mark GameObject as Static."
    ],
    [
        'title' => 'Frame Debugger',
        'content' => "Analyzes rendering.\n- Example: Open in Window > Analysis."
    ],
    [
        'title' => 'Memory Profiler',
        'content' => "Tracks memory usage.\n- Example: Check for leaks."
    ]
];

$csharpCards = [
    [
        'title' => 'Variables and Types',
        'content' => "C# is strongly typed.\n- Common types: `int`, `float`, `string`.\n- Example: `int health = 100; float speed = 5.5f;`"
    ],
    [
        'title' => 'Loops',
        'content' => "Use `for`, `while`, or `foreach` for iteration.\n- Example: `for (int i = 0; i < 5; i++) { Debug.Log(i); }`"
    ],
    [
        'title' => 'Classes',
        'content' => "Classes define objects.\n- Example: `public class Player { public int health; public void TakeDamage(int damage) { health -= damage; } }`"
    ],
    [
        'title' => 'Methods',
        'content' => "Functions in classes.\n- Example: `void Move() { position += speed; }`"
    ],
    [
        'title' => 'Constructors',
        'content' => "Initialize objects.\n- Example: `public Player(int hp) { health = hp; }`"
    ],
    [
        'title' => 'Properties',
        'content' => "Control access to fields.\n- Example: `public int Health { get; set; }`"
    ],
    [
        'title' => 'Inheritance',
        'content' => "Classes inherit behavior.\n- Example: `public class Enemy : Character {}`"
    ],
    [
        'title' => 'Polymorphism',
        'content' => "Override methods.\n- Example: `public override void Attack() { base.Attack(); }`"
    ],
    [
        'title' => 'Interfaces',
        'content' => "Define contracts.\n- Example: `public interface IDamageable { void TakeDamage(int dmg); }`"
    ],
    [
        'title' => 'Abstract Classes',
        'content' => "Cannot be instantiated.\n- Example: `public abstract class Vehicle { public abstract void Move(); }`"
    ],
    [
        'title' => 'Structs',
        'content' => "Value types.\n- Example: `public struct Point { public int x, y; }`"
    ],
    [
        'title' => 'Enums',
        'content' => "Define named constants.\n- Example: `public enum State { Idle, Running }`"
    ],
    [
        'title' => 'Arrays',
        'content' => "Fixed-size collections.\n- Example: `int[] scores = new int[5];`"
    ],
    [
        'title' => 'Lists',
        'content' => "Dynamic collections.\n- Example: `List<string> names = new List<string>();`"
    ],
    [
        'title' => 'Dictionaries',
        'content' => "Key-value pairs.\n- Example: `Dictionary<string, int> stats = new Dictionary<string, int>();`"
    ],
    [
        'title' => 'LINQ',
        'content' => "Query collections.\n- Example: `var highScores = scores.Where(s => s > 100);`"
    ],
    [
        'title' => 'Delegates',
        'content' => "Function pointers.\n- Example: `public delegate void Action();`"
    ],
    [
        'title' => 'Events',
        'content' => "Notify subscribers.\n- Example: `public event Action OnDeath;`"
    ],
    [
        'title' => 'Lambda Expressions',
        'content' => "Inline functions.\n- Example: `Func<int, int> square = x => x * x;`"
    ],
    [
        'title' => 'Nullable Types',
        'content' => "Allow null values.\n- Example: `int? count = null;`"
    ],
    [
        'title' => 'Generics',
        'content' => "Type-safe templates.\n- Example: `public class Stack<T> {}`"
    ],
    [
        'title' => 'Exception Handling',
        'content' => "Manages errors.\n- Example: `try { int x = 0; } catch (Exception e) { Debug.Log(e); }`"
    ],
    [
        'title' => 'Static Members',
        'content' => "Shared across instances.\n- Example: `public static int count = 0;`"
    ],
    [
        'title' => 'Constants',
        'content' => "Immutable values.\n- Example: `public const float Gravity = 9.81f;`"
    ],
    [
        'title' => 'ReadOnly',
        'content' => "Set once at runtime.\n- Example: `public readonly int id = 1;`"
    ],
    [
        'title' => 'Access Modifiers',
        'content' => "Control visibility.\n- Types: `public`, `private`, `protected`.\n- Example: `private int health;`"
    ],
    [
        'title' => 'Method Overloading',
        'content' => "Same name, different parameters.\n- Example: `void Move(int x); void Move(float x);`"
    ],
    [
        'title' => 'Operator Overloading',
        'content' => "Redefine operators.\n- Example: `public static Vector operator +(Vector a, Vector b);`"
    ],
    [
        'title' => 'Extension Methods',
        'content' => "Add methods to types.\n- Example: `public static class StringExt { public static bool IsEmpty(this string s) { return s.Length == 0; } }`"
    ],
    [
        'title' => 'Async/Await',
        'content' => "Asynchronous code.\n- Example: `async Task Wait() { await Task.Delay(1000); }`"
    ],
    [
        'title' => 'Tasks',
        'content' => "Manage async operations.\n- Example: `Task.Run(() => DoWork());`"
    ],
    [
        'title' => 'Threads',
        'content' => "Parallel execution.\n- Example: `Thread t = new Thread(() => Run()); t.Start();`"
    ],
    [
        'title' => 'String Interpolation',
        'content' => "Embed variables in strings.\n- Example: `string msg = $'Score: {score}';`"
    ],
    [
        'title' => 'StringBuilder',
        'content' => "Efficient string manipulation.\n- Example: `StringBuilder sb = new StringBuilder(); sb.Append('Hi');`"
    ],
    [
        'title' => 'DateTime',
        'content' => "Handles dates.\n- Example: `DateTime now = DateTime.Now;`"
    ],
    [
        'title' => 'Random',
        'content' => "Generates random numbers.\n- Example: `Random rand = new Random(); int num = rand.Next(1, 10);`"
    ],
    [
        'title' => 'Math',
        'content' => "Math operations.\n- Example: `double result = Math.Sqrt(16);`"
    ],
    [
        'title' => 'File IO',
        'content' => "Read/write files.\n- Example: `File.WriteAllText('data.txt', 'Hello');`"
    ],
    [
        'title' => 'StreamReader',
        'content' => "Reads text files.\n- Example: `using (StreamReader sr = new StreamReader('file.txt')) { string line = sr.ReadLine(); }`"
    ],
    [
        'title' => 'StreamWriter',
        'content' => "Writes text files.\n- Example: `using (StreamWriter sw = new StreamWriter('file.txt')) { sw.WriteLine('Hi'); }`"
    ],
    [
        'title' => 'JSON Serialization',
        'content' => "Convert objects to JSON.\n- Example: `string json = JsonUtility.ToJson(obj);`"
    ],
    [
        'title' => 'Attributes',
        'content' => "Add metadata.\n- Example: `[Obsolete('Use NewMethod')]`"
    ],
    [
        'title' => 'Reflection',
        'content' => "Inspect types at runtime.\n- Example: `Type type = typeof(Player);`"
    ],
    [
        'title' => 'Dynamic',
        'content' => "Bypass static typing.\n- Example: `dynamic obj = new ExpandoObject();`"
    ],
    [
        'title' => 'Tuple',
        'content' => "Group values.\n- Example: `(int, string) pair = (1, 'test');`"
    ],
    [
        'title' => 'ValueTuple',
        'content' => "Named tuples.\n- Example: `var point = (x: 1, y: 2);`"
    ],
    [
        'title' => 'Pattern Matching',
        'content' => "Simplify conditionals.\n- Example: `if (obj is string s) { Debug.Log(s); }`"
    ],
    [
        'title' => 'Switch Expression',
        'content' => "Concise switch.\n- Example: `var result = x switch { 1 => 'One', _ => 'Other' };`"
    ],
    [
        'title' => 'Record Types',
        'content' => "Immutable data classes.\n- Example: `public record Person(string Name, int Age);`"
    ],
    [
        'title' => 'Init-Only Properties',
        'content' => "Set during init.\n- Example: `public int Age { get; init; }`"
    ],
    [
        'title' => 'Null Coalescing',
        'content' => "Handle nulls.\n- Example: `string name = input ?? 'Default';`"
    ],
    [
        'title' => 'Null Conditional',
        'content' => "Safe navigation.\n- Example: `int? length = name?.Length;`"
    ],
    [
        'title' => 'Default Interface Methods',
        'content' => "Add methods to interfaces.\n- Example: `void Log() { Debug.Log('Default'); }`"
    ],
    [
        'title' => 'Covariance',
        'content' => "Allows derived types.\n- Example: `IEnumerable<object> list = new List<string>();`"
    ],
    [
        'title' => 'Contravariance',
        'content' => "Allows base types.\n- Example: `Action<object> act = (string s) => {};`"
    ],
    [
        'title' => 'Indexers',
        'content' => "Array-like access.\n- Example: `public int this[int i] { get => data[i]; }`"
    ],
    [
        'title' => 'Partial Classes',
        'content' => "Split class definitions.\n- Example: `partial class Player {}`"
    ],
    [
        'title' => 'Using Statement',
        'content' => "Manages resources.\n- Example: `using (var file = File.Open('data.txt', FileMode.Open)) {}`"
    ],
    [
        'title' => 'Global Using',
        'content' => "Apply using everywhere.\n- Example: `global using System;`"
    ],
    [
        'title' => 'Implicit Conversion',
        'content' => "Automatic type cast.\n- Example: `public static implicit operator int(MyClass c) => c.Value;`"
    ],
    [
        'title' => 'Explicit Conversion',
        'content' => "Manual type cast.\n- Example: `public static explicit operator MyClass(int i);`"
    ],
    [
        'title' => 'Sealed Classes',
        'content' => "Prevent inheritance.\n- Example: `public sealed class Final {}`"
    ],
    [
        'title' => 'Base Keyword',
        'content' => "Access base class.\n- Example: `base.Move();`"
    ],
    [
        'title' => 'This Keyword',
        'content' => "Refer to current instance.\n- Example: `this.health = 100;`"
    ],
    [
        'title' => 'Out Parameters',
        'content' => "Return multiple values.\n- Example: `void GetData(out int x) { x = 10; }`"
    ],
    [
        'title' => 'Ref Parameters',
        'content' => "Pass by reference.\n- Example: `void Swap(ref int x, ref int y) {}`"
    ],
    [
        'title' => 'In Parameters',
        'content' => "Read-only reference.\n- Example: `void Read(in int x) {}`"
    ],
    [
        'title' => 'Params',
        'content' => "Variable arguments.\n- Example: `void Log(params object[] items) {}`"
    ],
    [
        'title' => 'Yield Return',
        'content' => "Create iterators.\n- Example: `IEnumerable<int> Numbers() { yield return 1; }`"
    ],
    [
        'title' => 'Anonymous Types',
        'content' => "Temporary types.\n- Example: `var obj = new { Name = 'Test', Age = 20 };`"
    ],
    [
        'title' => 'Local Functions',
        'content' => "Functions in methods.\n- Example: `int Add(int x) { int Double() => x * 2; return Double(); }`"
    ],
    [
        'title' => 'Expression-Bodied Members',
        'content' => "Concise syntax.\n- Example: `int Double(int x) => x * 2;`"
    ],
    [
        'title' => 'Auto-Implemented Properties',
        'content' => "Simplify properties.\n- Example: `public int Age { get; set; }`"
    ],
    [
        'title' => 'Nameof Operator',
        'content' => "Get name of variable.\n- Example: `string name = nameof(health);`"
    ],
    [
        'title' => 'Typeof Operator',
        'content' => "Get type info.\n- Example: `Type t = typeof(int);`"
    ],
    [
        'title' => 'Is Operator',
        'content' => "Check type.\n- Example: `if (obj is Player p) {}`"
    ],
    [
        'title' => 'As Operator',
        'content' => "Safe cast.\n- Example: `Player p = obj as Player;`"
    ],
    [
        'title' => 'Checked/Unchecked',
        'content' => "Control overflow.\n- Example: `checked { int x = int.MaxValue + 1; }`"
    ],
    [
        'title' => 'Fixed Statement',
        'content' => "Pin memory.\n- Example: `fixed (int* p = array) {}`"
    ],
    [
        'title' => 'Stackalloc',
        'content' => "Allocate on stack.\n- Example: `Span<int> numbers = stackalloc int[10];`"
    ],
    [
        'title' => 'Span<T>',
        'content' => "Memory-efficient arrays.\n- Example: `Span<int> slice = array.AsSpan(0, 5);`"
    ],
    [
        'title' => 'Memory<T>',
        'content' => "Manages memory.\n- Example: `Memory<int> mem = array.AsMemory();`"
    ],
    [
        'title' => 'Unsafe Code',
        'content' => "Use pointers.\n- Example: `unsafe { int* p = &x; }`"
    ],
    [
        'title' => 'Lock Statement',
        'content' => "Thread safety.\n- Example: `lock (obj) { counter++; }`"
    ],
    [
        'title' => 'Volatile',
        'content' => "Prevent compiler optimizations.\n- Example: `public volatile int flag;`"
    ],
    [
        'title' => 'Interlocked',
        'content' => "Atomic operations.\n- Example: `Interlocked.Increment(ref count);`"
    ],
    [
        'title' => 'Mutex',
        'content' => "Synchronize threads.\n- Example: `Mutex mutex = new Mutex(); mutex.WaitOne();`"
    ],
    [
        'title' => 'Semaphore',
        'content' => "Limit resource access.\n- Example: `SemaphoreSlim sem = new SemaphoreSlim(3);`"
    ],
    [
        'title' => 'CancellationToken',
        'content' => "Cancel tasks.\n- Example: `Task.Run(() => Work(), token);`"
    ],
    [
        'title' => 'Parallel',
        'content' => "Parallel processing.\n- Example: `Parallel.For(0, 10, i => DoWork(i));`"
    ],
    [
        'title' => 'PLINQ',
        'content' => "Parallel LINQ.\n- Example: `var result = data.AsParallel().Where(x => x > 0);`"
    ],
    [
        'title' => 'WeakReference',
        'content' => "Avoid memory leaks.\n- Example: `WeakReference wr = new WeakReference(obj);`"
    ],
    [
        'title' => 'GC',
        'content' => "Garbage Collector.\n- Example: `GC.Collect();`"
    ],
    [
        'title' => 'Disposable',
        'content' => "Manage resources.\n- Example: `public void Dispose() { resource = null; }`"
    ],
    [
        'title' => 'Bitwise Operators',
        'content' => "Manipulate bits.\n- Example: `int result = a & b;`"
    ],
    [
        'title' => 'BigInteger',
        'content' => "Arbitrary-precision numbers.\n- Example: `BigInteger num = BigInteger.Parse('123456789');`"
    ],
    [
        'title' => 'Complex',
        'content' => "Complex numbers.\n- Example: `Complex c = new Complex(3, 4);`"
    ]
];

$testingCards = [
    [
        'title' => 'Unit Testing',
        'content' => "Tests individual functions or methods.\n- Use NUnit in Unity.\n- Example: `[Test] public void TestHealth() { Assert.AreEqual(100, player.health); }`"
    ],
    [
        'title' => 'NUnit Framework',
        'content' => "Popular testing framework for C#.\n- Attributes: `[Test]`, `[SetUp]`.\n- Example: `using NUnit.Framework; [Test] public void Test() { Assert.IsTrue(true); }`"
    ],
    [
        'title' => 'Unity Test Framework',
        'content' => "Built-in testing for Unity.\n- Supports EditMode and PlayMode.\n- Example: Open Test Runner in Window > General > Test Runner."
    ],
    [
        'title' => 'Test Runner',
        'content' => "Unity’s UI for running tests.\n- Run tests in Editor or on device.\n- Example: Click ‘Run All’ in Test Runner window."
    ],
    [
        'title' => 'EditMode Tests',
        'content' => "Run in Editor without entering PlayMode.\n- Use for logic tests.\n- Example: `[Test] public void TestLogic() { Assert.IsNotNull(gameObject); }`"
    ],
    [
        'title' => 'PlayMode Tests',
        'content' => "Run in PlayMode to test runtime behavior.\n- Example: `[UnityTest] public IEnumerator TestMovement() { yield return null; Assert.IsTrue(player.transform.position.x > 0); }`"
    ],
    [
        'title' => 'Assert Class',
        'content' => "Verifies test conditions.\n- Methods: `AreEqual`, `IsTrue`.\n- Example: `Assert.AreEqual(expected, actual);`"
    ],
    [
        'title' => 'SetUp Attribute',
        'content' => "Runs before each test.\n- Initializes test environment.\n- Example: `[SetUp] public void Init() { player = new GameObject(); }`"
    ],
    [
        'title' => 'TearDown Attribute',
        'content' => "Runs after each test.\n- Cleans up resources.\n- Example: `[TearDown] public void Cleanup() { Object.Destroy(player); }`"
    ],
    [
        'title' => 'TestFixture',
        'content' => "Groups related tests.\n- Example: `[TestFixture] public class PlayerTests {}`"
    ],
    [
        'title' => 'Mocking',
        'content' => "Simulates dependencies.\n- Use Moq for C#.\n- Example: `var mock = new Mock<IPlayer>(); mock.Setup(p => p.Health).Returns(100);`"
    ],
    [
        'title' => 'Moq Framework',
        'content' => "Creates mock objects for testing.\n- Install via NuGet.\n- Example: `Mock<IEnemy> enemyMock = new Mock<IEnemy>();`"
    ],
    [
        'title' => 'Integration Testing',
        'content' => "Tests interactions between components.\n- Example: Test if Player and Enemy scripts interact correctly."
    ],
    [
        'title' => 'Test Doubles',
        'content' => "Substitutes for real objects.\n- Types: Stubs, Mocks.\n- Example: Create a stub for a database."
    ],
    [
        'title' => 'Stub',
        'content' => "Provides predefined responses.\n- Example: `public class StubHealth : IHealth { public int GetHealth() { return 100; } }`"
    ],
    [
        'title' => 'Fake',
        'content' => "Simplified implementation for testing.\n- Example: Fake a server response for network tests."
    ],
    [
        'title' => 'Test-Driven Development',
        'content' => "Write tests before code.\n- Steps: Write test, run, write code, refactor.\n- Example: Write test for `Player.TakeDamage()`."
    ],
    [
        'title' => 'Red-Green-Refactor',
        'content' => "TDD cycle: Fail test (red), pass test (green), improve code.\n- Example: Start with failing `Assert.IsTrue(false);`."
    ],
    [
        'title' => 'Code Coverage',
        'content' => "Measures tested code percentage.\n- Use tools like JetBrains dotCover.\n- Example: Aim for 80% coverage."
    ],
    [
        'title' => 'Test Attributes',
        'content' => "Customize test behavior.\n- Examples: `[Ignore]`, `[Category('Fast')]`.\n- Example: `[Test][Category('Player')] public void TestMove() {}`"
    ],
    [
        'title' => 'Parameterized Tests',
        'content' => "Run tests with multiple inputs.\n- Example: `[TestCase(10, 5, 5)] public void TestDamage(int hp, int dmg, int expected) { Assert.AreEqual(expected, hp - dmg); }`"
    ],
    [
        'title' => 'ExpectedException',
        'content' => "Tests for thrown exceptions.\n- Example: `[Test][ExpectedException(typeof(ArgumentException))] public void TestInvalid() { throw new ArgumentException(); }`"
    ],
    [
        'title' => 'Coroutine Testing',
        'content' => "Tests Unity coroutines.\n- Use `UnityTest` and `yield return`.\n- Example: `[UnityTest] public IEnumerator TestWait() { yield return new WaitForSeconds(1); Assert.IsTrue(true); }`"
    ],
    [
        'title' => 'Scene Testing',
        'content' => "Tests GameObjects in a scene.\n- Load scene in PlayMode.\n- Example: `SceneManager.LoadScene('TestScene');`"
    ],
    [
        'title' => 'UI Testing',
        'content' => "Tests UI elements like buttons.\n- Example: `[UnityTest] public IEnumerator TestButtonClick() { button.onClick.Invoke(); yield return null; Assert.IsTrue(clicked); }`"
    ],
    [
        'title' => 'Performance Testing',
        'content' => "Measures code efficiency.\n- Use Unity Profiler.\n- Example: Check frame rate drop in heavy scenes."
    ],
    [
        'title' => 'Load Testing',
        'content' => "Tests system under high load.\n- Example: Spawn 1000 GameObjects and measure FPS."
    ],
    [
        'title' => 'Stress Testing',
        'content' => "Pushes system to breaking point.\n- Example: Run 10,000 physics calculations."
    ],
    [
        'title' => 'Regression Testing',
        'content' => "Ensures new changes don’t break old code.\n- Example: Rerun all tests after a bug fix."
    ],
    [
        'title' => 'Smoke Testing',
        'content' => "Verifies basic functionality.\n- Example: Test if game starts without crashing."
    ],
    [
        'title' => 'End-to-End Testing',
        'content' => "Tests entire workflow.\n- Example: Test from main menu to gameplay."
    ],
    [
        'title' => 'Acceptance Testing',
        'content' => "Verifies user requirements.\n- Example: Test if player can complete a level."
    ],
    [
        'title' => 'Test Suites',
        'content' => "Groups tests for organization.\n- Example: Create suite for `PlayerTests` and `EnemyTests`."
    ],
    [
        'title' => 'Continuous Integration',
        'content' => "Automates testing on code commits.\n- Tools: Jenkins, GitHub Actions.\n- Example: Run tests on every push."
    ],
    [
        'title' => 'Test Automation',
        'content' => "Scripts run tests automatically.\n- Example: Use Unity Test Runner in CI pipeline."
    ],
    [
        'title' => 'Test Coverage Tools',
        'content' => "Analyze tested code.\n- Example: Use OpenCover for C# projects."
    ],
    [
        'title' => 'Behavior-Driven Development',
        'content' => "Focuses on user behavior.\n- Use tools like SpecFlow.\n- Example: Write tests in Gherkin format."
    ],
    [
        'title' => 'Gherkin Syntax',
        'content' => "Used in BDD for readable tests.\n- Example: `Given player health is 100, When hit by enemy, Then health is 90.`"
    ],
    [
        'title' => 'Test Setup Scripts',
        'content' => "Prepare test environments.\n- Example: `GameObject player = Instantiate(prefab);`"
    ],
    [
        'title' => 'Test Isolation',
        'content' => "Ensures tests don’t affect each other.\n- Example: Reset scene state in `[TearDown]`."
    ],
    [
        'title' => 'Test Data',
        'content' => "Prepares data for tests.\n- Example: `var testPlayer = new Player { Health = 100 };`"
    ],
    [
        'title' => 'Test Fixtures',
        'content' => "Reusable test setups.\n- Example: Share `[SetUp]` across test classes."
    ],
    [
        'title' => 'Randomized Testing',
        'content' => "Tests with random inputs.\n- Example: `int dmg = Random.Range(1, 10); Assert.IsTrue(player.Health > 0);`"
    ],
    [
        'title' => 'Boundary Testing',
        'content' => "Tests edge cases.\n- Example: `Assert.Throws<Exception>(() => player.Health = -1);`"
    ],
    [
        'title' => 'Equivalence Partitioning',
        'content' => "Groups similar inputs.\n- Example: Test health values 0, 50, 100."
    ],
    [
        'title' => 'State-Based Testing',
        'content' => "Verifies object state.\n- Example: `player.TakeDamage(10); Assert.AreEqual(90, player.Health);`"
    ],
    [
        'title' => 'Interaction Testing',
        'content' => "Verifies method calls.\n- Example: `mock.Verify(m => m.Attack(), Times.Once());`"
    ],
    [
        'title' => 'Test Naming',
        'content' => "Clear, descriptive test names.\n- Example: `Test_Player_TakesDamage_ReduceHealth`."
    ],
    [
        'title' => 'Test Organization',
        'content' => "Group tests by feature.\n- Example: Folder `Tests/Player/` for player-related tests."
    ],
    [
        'title' => 'Test Attributes',
        'content' => "Mark tests for specific platforms.\n- Example: `[Test][Platform('PC')]`"
    ],
    [
        'title' => 'Test Timeout',
        'content' => "Limits test duration.\n- Example: `[Test][Timeout(1000)] public void TestFast() {}`"
    ],
    [
        'title' => 'Test Retry',
        'content' => "Retries flaky tests.\n- Example: `[Test][Retry(3)] public void TestUnstable() {}`"
    ],
    [
        'title' => 'Flaky Tests',
        'content' => "Tests with inconsistent results.\n- Example: Fix by removing randomness in tests."
    ],
    [
        'title' => 'Test Logging',
        'content' => "Logs test execution.\n- Example: `Debug.Log('Test started');`"
    ],
    [
        'title' => 'Test Reports',
        'content' => "Summarizes test results.\n- Example: Export XML from Test Runner."
    ],
    [
        'title' => 'Test Debugging',
        'content' => "Diagnoses test failures.\n- Example: Use breakpoints in Visual Studio."
    ],
    [
        'title' => 'Test Assertions',
        'content' => "Verifies expected outcomes.\n- Example: `Assert.IsFalse(player.IsDead);`"
    ],
    [
        'title' => 'Soft Assertions',
        'content' => "Continues test after failure.\n- Example: Use `Assert.Multiple` in NUnit."
    ],
    [
        'title' => 'Test Context',
        'content' => "Accesses test metadata.\n- Example: `TestContext.CurrentContext.Test.Name`"
    ],
    [
        'title' => 'Test Cleanup',
        'content' => "Resets environment.\n- Example: `SceneManager.UnloadSceneAsync('TestScene');`"
    ],
    [
        'title' => 'Test Environment',
        'content' => "Configures test conditions.\n- Example: Set `Time.timeScale = 1` in `[SetUp]`."
    ],
    [
        'title' => 'Test Scenarios',
        'content' => "Tests specific use cases.\n- Example: Test player death when health is 0."
    ],
    [
        'title' => 'Test Matrix',
        'content' => "Tests across configurations.\n- Example: Test on Windows, macOS, Android."
    ],
    [
        'title' => 'Test Prioritization',
        'content' => "Runs critical tests first.\n- Example: Mark with `[Category('Critical')]`."
    ],
    [
        'title' => 'Test Parallelization',
        'content' => "Runs tests concurrently.\n- Example: Enable in NUnit settings."
    ],
    [
        'title' => 'Test Dependencies',
        'content' => "Runs tests in order.\n- Example: `[Test][Order(1)] public void TestFirst() {}`"
    ],
    [
        'title' => 'Test Data Generators',
        'content' => "Creates test inputs.\n- Example: `AutoFixture` for random data."
    ],
    [
        'title' => 'Test Hooks',
        'content' => "Customizes test lifecycle.\n- Example: Use `[OneTimeSetUp]` for suite setup."
    ],
    [
        'title' => 'Test Annotations',
        'content' => "Adds metadata to tests.\n- Example: `[Description('Tests player movement')]`"
    ],
    [
        'title' => 'Test Frameworks Comparison',
        'content' => "NUnit vs xUnit vs MSTest.\n- Example: NUnit is default in Unity."
    ],
    [
        'title' => 'Test-Driven Debugging',
        'content' => "Uses tests to find bugs.\n- Example: Write test to reproduce issue."
    ],
    [
        'title' => 'Test Refactoring',
        'content' => "Improves test code.\n- Example: Extract common setup to method."
    ],
    [
        'title' => 'Test Maintenance',
        'content' => "Keeps tests up-to-date.\n- Example: Update tests after code changes."
    ],
    [
        'title' => 'Test Metrics',
        'content' => "Tracks test effectiveness.\n- Example: Measure test execution time."
    ],
    [
        'title' => 'Test Visualization',
        'content' => "Displays test results.\n- Example: Use Test Runner’s tree view."
    ],
    [
        'title' => 'Test Security',
        'content' => "Tests for vulnerabilities.\n- Example: Test for null reference errors."
    ],
    [
        'title' => 'Test Localization',
        'content' => "Tests multilingual support.\n- Example: Test UI text in English and Spanish."
    ],
    [
        'title' => 'Test Accessibility',
        'content' => "Tests for user accessibility.\n- Example: Test UI navigation with keyboard."
    ],
    [
        'title' => 'Test Compatibility',
        'content' => "Tests across platforms.\n- Example: Test on iOS and Android."
    ],
    [
        'title' => 'Test Scalability',
        'content' => "Tests system growth.\n- Example: Test with 100 vs 1000 players."
    ],
    [
        'title' => 'Test Robustness',
        'content' => "Tests error handling.\n- Example: Test with invalid inputs."
    ],
    [
        'title' => 'Test Recovery',
        'content' => "Tests system recovery.\n- Example: Test reload after crash."
    ],
    [
        'title' => 'Test Usability',
        'content' => "Tests user experience.\n- Example: Test if button is clickable."
    ],
    [
        'title' => 'Test Documentation',
        'content' => "Documents test cases.\n- Example: Add comments to test methods."
    ],
    [
        'title' => 'Test Versioning',
        'content' => "Tracks test changes.\n- Example: Use Git for test scripts."
    ],
    [
        'title' => 'Test Collaboration',
        'content' => "Shares tests with team.\n- Example: Store tests in shared repository."
    ],
    [
        'title' => 'Test Review',
        'content' => "Inspects test quality.\n- Example: Peer review test code."
    ],
    [
        'title' => 'Test Optimization',
        'content' => "Improves test speed.\n- Example: Avoid heavy setup in tests."
    ],
    [
        'title' => 'Test Validation',
        'content' => "Ensures tests are correct.\n- Example: Verify test logic manually."
    ],
    [
        'title' => 'Test Monitoring',
        'content' => "Tracks test execution.\n- Example: Log test results to file."
    ],
    [
        'title' => 'Test Feedback',
        'content' => "Improves code via tests.\n- Example: Fix code after test failure."
    ],
    [
        'title' => 'Test Simulation',
        'content' => "Simulates real-world conditions.\n- Example: Test with simulated network lag."
    ],
    [
        'title' => 'Test Exploration',
        'content' => "Finds edge cases.\n- Example: Test with extreme inputs."
    ],
    [
        'title' => 'Test Certification',
        'content' => "Ensures compliance.\n- Example: Test for platform requirements."
    ],
    [
        'title' => 'Test Maturity',
        'content' => "Improves testing process.\n- Example: Adopt TDD across project."
    ],
    [
        'title' => 'Test Ethics',
        'content' => "Ensures fair testing.\n- Example: Avoid biased test cases."
    ],
    [
        'title' => 'Test Innovation',
        'content' => "Adopts new testing tools.\n- Example: Try Unity’s new test features."
    ]
];

$blockchainCards = [
    [
        'title' => 'Blockchain Basics',
        'content' => "Blockchain is a decentralized ledger for secure transactions.\n- Used in Unity for NFTs and ownership.\n- Example: Ethereum, Polygon blockchains."
    ],
    [
        'title' => 'Web3 in Unity',
        'content' => "Web3 enables decentralized apps \\(dApps\\) in Unity.\n- Integrates blockchain for asset ownership.\n- Example: Use Moralis SDK for Web3 login."
    ],
    [
        'title' => 'NFTs in Games',
        'content' => "Non-Fungible Tokens \\(NFTs\\) represent unique in-game assets.\n- Example: ERC721 for a sword NFT."
    ],
    [
        'title' => 'Smart Contracts',
        'content' => "Self-executing contracts on blockchain.\n- Written in Solidity.\n- Example: `function mint(address to, uint256 id) public {}`"
    ],
    [
        'title' => 'Unity Asset Store',
        'content' => "Offers blockchain SDKs for Unity.\n- Example: Tezos SDK for NFT integration.\nCheck: Unity Asset Store."
    ],
    [
        'title' => 'MetaMask Integration',
        'content' => "Connects Unity to Ethereum wallets.\n- Example: Use Nethereum for MetaMask login."
    ],
    [
        'title' => 'Polygon Blockchain',
        'content' => "Scalable Ethereum layer-2 for Unity.\n- Low gas fees.\n- Example: Deploy NFTs with Moralis SDK."
    ],
    [
        'title' => 'Ethereum in Unity',
        'content' => "Popular blockchain for Unity games.\n- Supports NFTs, smart contracts.\n- Example: Use Web3Unity library."
    ],
    [
        'title' => 'NEAR Blockchain',
        'content' => "Fast blockchain for Unity games.\n- Example: Integrate NEAR wallet for authentication."
    ],
    [
        'title' => 'WAX Blockchain',
        'content' => "Optimized for NFTs in games.\n- Example: Mint in-game NFTs on WAX."
    ],
    [
        'title' => 'Tezos SDK',
        'content' => "Unity SDK for Tezos blockchain.\n- Supports wallets, NFTs.\n- Example: Authenticate players with Tezos wallet."
    ],
    [
        'title' => 'Moralis SDK',
        'content' => "Simplifies Web3 in Unity.\n- Handles wallet login, transactions.\n- Example: `await Moralis.Authenticate\\(\\);`"
    ],
    [
        'title' => 'Arkane Plugin',
        'content' => "Unity plugin for Polygon NFTs.\n- Manages in-game assets.\n- Example: Publish items to Polygon blockchain."
    ],
    [
        'title' => 'Nethereum Library',
        'content' => "C# library for Ethereum in Unity.\n- Example: `var web3 = new Web3\\(\"https://mainnet.infura.io/v3/YOUR_KEY\"\\);`"
    ],
    [
        'title' => 'Wallet Authentication',
        'content' => "Verifies players via blockchain wallets.\n- Example: `await Moralis.ConnectWallet\\(\\);`"
    ],
    [
        'title' => 'Decentralized Ownership',
        'content' => "Players own in-game assets on blockchain.\n- Example: Sword NFT stored on Ethereum."
    ],
    [
        'title' => 'In-Game Marketplace',
        'content' => "Trade assets on blockchain.\n- Example: `contract.sellItem\\(itemId, price\\);`"
    ],
    [
        'title' => 'Play-to-Earn',
        'content' => "Players earn crypto or NFTs by playing.\n- Example: Reward NFT for completing a quest."
    ],
    [
        'title' => 'GameCredits',
        'content' => "Cryptocurrency for Unity games.\n- Example: Use GAME token for in-game purchases."
    ],
    [
        'title' => 'Solidity Basics',
        'content' => "Language for smart contracts.\n- Example: `pragma solidity ^0.8.0; contract Game {}`"
    ],
    [
        'title' => 'ERC721 Standard',
        'content' => "NFT standard for unique tokens.\n- Example: `function transferFrom(address from, address to, uint256 tokenId) public;`"
    ],
    [
        'title' => 'ERC1155 Standard',
        'content' => "Multi-token standard for NFTs.\n- Example: Supports batch transfers of assets."
    ],
    [
        'title' => 'Gas Fees',
        'content' => "Cost of blockchain transactions.\n- Lower on Polygon, NEAR.\n- Example: Optimize contracts to reduce gas usage."
    ],
    [
        'title' => 'Web3 Libraries',
        'content' => "Connect Unity to blockchain.\n- Example: Web3Unity, Moralis SDKs."
    ],
    [
        'title' => 'Minting NFTs',
        'content' => "Create NFTs on blockchain.\n- Example: `contract.mint\\(playerAddress, tokenId\\);`"
    ],
    [
        'title' => 'Smart Contract Deployment',
        'content' => "Deploy contracts to blockchain.\n- Example: Use Remix IDE to deploy on Ethereum."
    ],
    [
        'title' => 'Infura API',
        'content' => "Connects Unity to Ethereum nodes.\n- Example: `var web3 = new Web3\\(\"https://mainnet.infura.io/v3/YOUR_KEY\"\\);`"
    ],
    [
        'title' => 'Cross-Chain Support',
        'content' => "Use multiple blockchains in Unity.\n- Example: Moralis supports Ethereum, Polygon."
    ],
    [
        'title' => 'Blockchain Security',
        'content' => "Protect smart contracts from hacks.\n- Example: Use OpenZeppelin for secure code."
    ],
    [
        'title' => 'Smart Contract Audit',
        'content' => "Verify contracts for bugs.\n- Example: Audit before deploying to mainnet."
    ],
    [
        'title' => 'Decentralized Marketplace',
        'content' => "Players trade NFTs peer-to-peer.\n- Example: Integrate OpenSea API for trading."
    ],
    [
        'title' => 'Player Wallets',
        'content' => "Store player assets securely.\n- Example: `string address = Moralis.GetWalletAddress\\(\\);`"
    ],
    [
        'title' => 'On-Chain Leaderboards',
        'content' => "Store scores on blockchain.\n- Example: `contract.updateScore\\(player, score\\);`"
    ],
    [
        'title' => 'Tokenomics',
        'content' => "Design in-game economy with tokens.\n- Example: Reward tokens for achievements."
    ],
    [
        'title' => 'Unity Verified Solutions',
        'content' => "Vetted blockchain tools for Unity.\n- Example: MetaMask, Tezos SDK."
    ],
    [
        'title' => 'Skale Blockchain',
        'content' => "Gasless blockchain for Unity games.\n- Example: Deploy games on Skale network."
    ],
    [
        'title' => 'Photon and Blockchain',
        'content' => "Combine multiplayer with NFTs.\n- Example: Sync NFT ownership via Photon."
    ],
    [
        'title' => 'Metaverse Games',
        'content' => "Unity games with blockchain economies.\n- Example: Virtual land as NFTs."
    ],
    [
        'title' => 'Blockchain Transactions',
        'content' => "Execute in-game purchases.\n- Example: `web3.eth.sendTransaction\\({to: address, value: amount}\\);`"
    ],
    [
        'title' => 'OpenZeppelin',
        'content' => "Library for secure smart contracts.\n- Example: `import \"@openzeppelin/contracts/token/ERC721/ERC721.sol\";`"
    ],
    [
        'title' => 'Hardhat',
        'content' => "Tool for smart contract development.\n- Example: `npx hardhat compile` to build contracts."
    ],
    [
        'title' => 'Truffle',
        'content' => "Framework for Ethereum contracts.\n- Example: `truffle migrate` to deploy contracts."
    ],
    [
        'title' => 'Remix IDE',
        'content' => "Online tool for Solidity development.\n- Example: Write and test contracts in browser."
    ],
    [
        'title' => 'IPFS Storage',
        'content' => "Store NFT metadata off-chain.\n- Example: Upload metadata to IPFS via Pinata."
    ],
    [
        'title' => 'Chainlink Oracles',
        'content' => "Fetch real-world data for Unity.\n- Example: Get crypto prices on-chain."
    ],
    [
        'title' => 'Web3 Authentication',
        'content' => "Login via blockchain wallet.\n- Example: `await Moralis.AuthenticateWithMetaMask\\(\\);`"
    ],
    [
        'title' => 'Blockchain Scalability',
        'content' => "Handle high transaction volumes.\n- Example: Use Polygon for low-cost scaling."
    ],
    [
        'title' => 'Gas Optimization',
        'content' => "Reduce transaction costs.\n- Example: Minimize storage in Solidity contracts."
    ],
    [
        'title' => 'Unity Blockchain SDKs',
        'content' => "Pre-built tools for blockchain integration.\n- Example: Enjin, Moralis SDKs."
    ],
    [
        'title' => 'Player-Driven Economies',
        'content' => "Let players control markets.\n- Example: Trade NFTs in-game."
    ],
    [
        'title' => 'Crypto Payments',
        'content' => "Accept crypto in Unity games.\n- Example: Integrate Coinbase Commerce API."
    ],
    [
        'title' => 'Unity and Solana',
        'content' => "Fast blockchain for Unity games.\n- Example: Use Solana SDK for NFT minting."
    ],
    [
        'title' => 'Unity and Algorand',
        'content' => "Quantum-resistant blockchain.\n- Example: Deploy assets with Algorand SDK."
    ],
    [
        'title' => 'Unity and Flow',
        'content' => "Gaming-focused blockchain.\n- Example: Mint collectibles on Flow."
    ],
    [
        'title' => 'NFT Interoperability',
        'content' => "Use NFTs across games.\n- Example: ERC1155 for multi-game assets."
    ],
    [
        'title' => 'Decentralized Identity',
        'content' => "Player identity via blockchain.\n- Example: Use wallet address as player ID."
    ],
    [
        'title' => 'Blockchain Analytics',
        'content' => "Track in-game transactions.\n- Example: Use Etherscan for Ethereum data."
    ],
    [
        'title' => 'Unity and GameFi',
        'content' => "Combine gaming with finance.\n- Example: Reward players with tokens."
    ],
    [
        'title' => 'NFT Rarity',
        'content' => "Assign rarity to in-game NFTs.\n- Example: Store rarity in NFT metadata."
    ],
    [
        'title' => 'Blockchain Testing',
        'content' => "Test smart contracts in Unity.\n- Example: Use Truffle for unit testing contracts."
    ],
    [
        'title' => 'Unity Multiplayer NFTs',
        'content' => "Sync NFTs in multiplayer games.\n- Example: Use Photon for NFT state sync."
    ],
    [
        'title' => 'In-Game Auctions',
        'content' => "Auction NFTs in Unity.\n- Example: `contract.bid\\(itemId, amount\\);`"
    ],
    [
        'title' => 'Blockchain UI',
        'content' => "Display NFT ownership in UI.\n- Example: `text.text = \"Owned: \" + nftId;`"
    ],
    [
        'title' => 'Unity and Enjin',
        'content' => "SDK for NFT integration.\n- Example: Mint items with Enjin SDK."
    ],
    [
        'title' => 'Blockchain Debugging',
        'content' => "Debug smart contract calls.\n- Example: Use Remix for transaction logs."
    ],
    [
        'title' => 'NFT Metadata',
        'content' => "Store NFT details off-chain.\n- Example: JSON with name, image URL."
    ],
    [
        'title' => 'Blockchain Events',
        'content' => "Listen for contract events.\n- Example: `contract.on\\(\"Minted\", \\(id\\) => {});`"
    ],
    [
        'title' => 'Unity Blockchain Tools',
        'content' => "Tools for Web3 integration.\n- Example: Moralis, Web3Unity libraries."
    ],
    [
        'title' => 'Crypto Wallets',
        'content' => "Manage in-game crypto.\n- Example: Integrate Trust Wallet."
    ],
    [
        'title' => 'Blockchain Governance',
        'content' => "Players vote on game rules.\n- Example: Use a DAO for decisions."
    ],
    [
        'title' => 'Unity and BNB Chain',
        'content' => "Fast blockchain for Unity.\n- Example: Deploy NFTs with Moralis."
    ],
    [
        'title' => 'NFT Crafting',
        'content' => "Combine items to create NFTs.\n- Example: `contract.craft\\(tokenId1, tokenId2\\);`"
    ],
    [
        'title' => 'Blockchain Latency',
        'content' => "Handle transaction delays.\n- Example: Show pending status in Unity UI."
    ],
    [
        'title' => 'Unity Blockchain Demos',
        'content' => "Test blockchain with demo scenes.\n- Example: Use Tezos SDK tutorials."
    ],
    [
        'title' => 'NFT Trading',
        'content' => "Players trade NFTs in-game.\n- Example: `contract.safeTransferFrom\\(from, to, id\\);`"
    ],
    [
        'title' => 'Blockchain APIs',
        'content' => "Fetch blockchain data in Unity.\n- Example: Use Moralis API for wallet balances."
    ],
    [
        'title' => 'Unity and Avalanche',
        'content' => "High-speed blockchain for games.\n- Example: Deploy assets on Avalanche."
    ],
    [
        'title' => 'NFT Staking',
        'content' => "Earn rewards by locking NFTs.\n- Example: `contract.stake\\(tokenId\\);`"
    ],
    [
        'title' => 'Blockchain UX',
        'content' => "Simplify blockchain for players.\n- Example: Hide gas fees in UI."
    ],
    [
        'title' => 'Unity Blockchain Security',
        'content' => "Protect player assets.\n- Example: Use 2FA for wallet authentication."
    ],
    [
        'title' => 'NFT Royalties',
        'content' => "Earn fees on NFT trades.\n- Example: Set royalty percentage in contract."
    ],
    [
        'title' => 'Blockchain Interoperability',
        'content' => "Use assets across blockchains.\n- Example: Bridge Ethereum to Polygon."
    ],
    [
        'title' => 'Unity Blockchain Tutorials',
        'content' => "Learn blockchain via Unity.\n- Example: Moralis YouTube tutorials."
    ],
    [
        'title' => 'NFT Marketplace UI',
        'content' => "Show NFT listings in Unity.\n- Example: `button.onClick.AddListener\\(() => BuyNFT\\(id\\)\\);`"
    ],
    [
        'title' => 'Blockchain Error Handling',
        'content' => "Manage transaction failures.\n- Example: `try { await contract.call\\(\\); } catch {}`"
    ],
    [
        'title' => 'Unity Blockchain Cost',
        'content' => "Budget for gas and hosting.\n- Example: Use Polygon for low transaction fees."
    ],
    [
        'title' => 'NFT Visuals',
        'content' => "Display NFT art in Unity.\n- Example: `spriteRenderer.sprite = nftSprite;`"
    ],
    [
        'title' => 'Blockchain Privacy',
        'content' => "Protect player data.\n- Example: Use zero-knowledge proofs."
    ],
    [
        'title' => 'Unity Blockchain Trends',
        'content' => "Future of Web3 gaming.\n- Example: Play-to-earn, metaverse games."
    ],
    [
        'title' => 'NFT Upgrades',
        'content' => "Enhance NFTs with new traits.\n- Example: `contract.upgrade\\(tokenId, trait\\);`"
    ],
    [
        'title' => 'Blockchain Community',
        'content' => "Join blockchain dev groups.\n- Example: Tezos Discord community."
    ],
    [
        'title' => 'Unity Blockchain Ethics',
        'content' => "Consider environmental impact.\n- Example: Use energy-efficient blockchains."
    ],
    [
        'title' => 'NFT Lending',
        'content' => "Loan NFTs for rewards.\n- Example: `contract.lend\\(tokenId, duration\\);`"
    ],
    [
        'title' => 'Blockchain Game Design',
        'content' => "Balance crypto rewards.\n- Example: Avoid pay-to-win mechanics."
    ],
    [
        'title' => 'Unity Blockchain Support',
        'content' => "Get help for Web3 integration.\n- Example: Unity forums, Moralis Discord."
    ],
    [
        'title' => 'NFT Analytics',
        'content' => "Track NFT trades in-game.\n- Example: Use Moralis for transaction history."
    ],
    [
        'title' => 'Blockchain Game Genres',
        'content' => "Choose genre for blockchain.\n- Example: RPGs for NFT collectibles."
    ],
    [
        'title' => 'Unity Blockchain Future',
        'content' => "Web3 gaming growth.\n- Example: Metaverse, cross-game NFTs."
    ]
];

$multiplayerCards = [
     [
        "title" => "Multiplayer Networking in Unity",
        "content" => "Unity supports multiplayer via various networking solutions.\n- Options: Photon, Mirror, Netcode for GameObjects.\n- Example: Use Photon for real-time card game syncing."
    ],
    [
        "title" => "Blockchain in Unity Games",
        "content" => "Blockchain enables secure, decentralized asset ownership.\n- Integrates NFTs for unique cards.\n- Example: Ethereum blockchain for trading card NFTs."
    ],
    [
        "title" => "Photon Unity Networking (PUN)",
        "content" => "Photon PUN simplifies multiplayer game development.\n- Supports real-time syncing for card games.\n- Example: Sync card plays across players with PUN RPCs."
    ],
    [
        "title" => "Mirror Networking Basics",
        "content" => "Mirror is an open-source networking library for Unity.\n- Ideal for LAN-based multiplayer card games.\n- Example: Use Mirror for turn-based card battles."
    ],
    [
        "title" => "Netcode for GameObjects",
        "content" => "Unity’s Netcode for GameObjects is a modern multiplayer solution.\n- Supports client-server and host models.\n- Example: Sync card decks in a 4-player game."
    ],
    [
        "title" => "NFT Integration in Unity",
        "content" => "NFTs allow players to own and trade digital cards.\n- Use Web3 SDKs like Moralis for integration.\n- Example: Fetch NFT cards from Ethereum wallet."
    ],
    [
        "title" => "Smart Contracts for Card Games",
        "content" => "Smart contracts automate game logic on blockchain.\n- Define rules for card trading or battles.\n- Example: Solidity contract for card ownership."
    ],
    [
        "title" => "Web3 Wallet Connection",
        "content" => "Connect Unity games to Web3 wallets like MetaMask.\n- Enables blockchain transactions.\n- Example: Login to view owned NFT cards."
    ],
    [
        "title" => "Play-to-Earn Mechanics",
        "content" => "Play-to-earn rewards players with crypto or NFTs.\n- Popular in blockchain card games.\n- Example: Earn tokens for winning matches."
    ],
    [
        "title" => "FishNet Networking",
        "content" => "FishNet is a lightweight networking library for Unity.\n- Optimized for low-bandwidth games like card games.\n- Example: Use FishNet for card game state sync."
    ],
    [
        "title" => "Ethereum Blockchain in Unity",
        "content" => "Ethereum is a popular blockchain for Unity games.\n- Supports NFTs and smart contracts.\n- Example: Mint card NFTs on Ethereum."
    ],
    [
        "title" => "Polygon for Scalability",
        "content" => "Polygon is a layer-2 solution for Ethereum.\n- Offers fast, low-cost transactions.\n- Example: Use Polygon for card trading."
    ],
    [
        "title" => "Algorand Blockchain",
        "content" => "Algorand provides fast, eco-friendly transactions.\n- Suitable for NFT card games.\n- Example: Algomond card game on Algorand."
    ],
    [
        "title" => "Flow Blockchain",
        "content" => "Flow is designed for consumer-friendly blockchain apps.\n- Ideal for auto-battler card games.\n- Example: Build an auto-battler on Flow."
    ],
    [
        "title" => "Moralis Web3 SDK",
        "content" => "Moralis simplifies blockchain integration in Unity.\n- Handles Web3 login and NFT fetching.\n- Example: Use Moralis for card ownership."
    ],
    [
        "title" => "Turn-Based Multiplayer",
        "content" => "Turn-based games reduce network load.\n- Ideal for card games with strategic play.\n- Example: Sync turns in a 2-player card duel."
    ],
    [
        "title" => "Real-Time Multiplayer",
        "content" => "Real-time multiplayer requires low-latency networking.\n- Use Photon or Netcode for fast sync.\n- Example: Real-time card battles with Photon."
    ],
    [
        "title" => "Unity UI for Card Games",
        "content" => "Unity UI enables drag-and-drop card mechanics.\n- Use Canvas for card layouts.\n- Example: Draggable cards in a multiplayer game."
    ],
    [
        "title" => "Decentralized Ownership",
        "content" => "Blockchain ensures players own their cards.\n- NFTs enable trading and selling.\n- Example: Trade cards on OpenSea."
    ],
    [
        "title" => "MetaMask Integration",
        "content" => "MetaMask connects Unity games to Ethereum.\n- Authenticate players via wallet.\n- Example: Login to access NFT card collection."
    ],
    [
        "title" => "Card Game Lobby System",
        "content" => "A lobby system matches players for multiplayer.\n- Use Photon Rooms or Unity Matchmaker.\n- Example: Join a 4-player card game lobby."
    ],
    [
        "title" => "Syncing Card States",
        "content" => "Sync card positions and states across players.\n- Use NetworkVariables in Netcode.\n- Example: Sync drawn cards in real time."
    ],
    [
        "title" => "NFT Minting in Unity",
        "content" => "Mint NFTs directly from Unity games.\n- Use smart contracts for minting logic.\n- Example: Mint a rare card NFT."
    ],
    [
        "title" => "In-Game Token Economy",
        "content" => "Tokens create in-game economies.\n- Use ERC-20 tokens for rewards.\n- Example: Reward players with tokens for wins."
    ],
    [
        "title" => "Matchmaking in Unity",
        "content" => "Matchmaking pairs players based on skill or rank.\n- Use Unity Relay for matchmaking.\n- Example: Match players for ranked card games."
    ],
    [
        "title" => "Card Animation Sync",
        "content" => "Sync card animations across multiplayer clients.\n- Use NetworkAnimator in Netcode.\n- Example: Animate card flips for all players."
    ],
    [
        "title" => "Blockchain Security",
        "content" => "Blockchain ensures secure transactions.\n- Use encryption for wallet connections.\n- Example: Secure NFT trades in-game."
    ],
    [
        "title" => "Unity Asset Store",
        "content" => "Unity Asset Store offers multiplayer and blockchain tools.\n- Find SDKs for Web3 integration.\n- Example: Use Ethereum SDK for NFTs."
    ],
    [
        "title" => "Photon Cloud",
        "content" => "Photon Cloud hosts multiplayer game servers.\n- Scales for global card games.\n- Example: Host a 100-player card tournament."
    ],
    [
        "title" => "Smart Contract Auditing",
        "content" => "Audit smart contracts for security.\n- Prevents bugs in card game logic.\n- Example: Audit contract for card trading."
    ],
    [
        "title" => "Cross-Platform Multiplayer",
        "content" => "Unity supports multiplayer across platforms.\n- Use Netcode for mobile and PC.\n- Example: Cross-platform card game battles."
    ],
    [
        "title" => "Card Game Logic",
        "content" => "Implement game logic for card interactions.\n- Use C# scripts in Unity.\n- Example: Script for card battle outcomes."
    ],
    [
        "title" => "NEAR Blockchain",
        "content" => "NEAR is a scalable blockchain for Unity games.\n- Supports NFT and wallet integration.\n- Example: Mint cards on NEAR."
    ],
    [
        "title" => "Decentralized Leaderboards",
        "content" => "Store leaderboards on blockchain for transparency.\n- Use smart contracts for rankings.\n- Example: Card game leaderboard on Ethereum."
    ],
    [
        "title" => "Card Rarity System",
        "content" => "Implement rarity for NFT cards.\n- Use metadata in smart contracts.\n- Example: Rare card with unique stats."
    ],
    [
        "title" => "Unity Relay Service",
        "content" => "Unity Relay facilitates multiplayer connections.\n- Low-latency for card games.\n- Example: Relay for 2-player card matches."
    ],
    [
        "title" => "WebGL Builds for Blockchain",
        "content" => "WebGL builds support browser-based blockchain games.\n- Integrate with MetaMask.\n- Example: Card game playable on web."
    ],
    [
        "title" => "Card Deck Management",
        "content" => "Manage card decks in multiplayer games.\n- Sync deck state via networking.\n- Example: Shuffle and draw cards for players."
    ],
    [
        "title" => "Blockchain Transaction Fees",
        "content" => "Blockchain transactions incur gas fees.\n- Use layer-2 solutions like Polygon.\n- Example: Low-cost card trades on Polygon."
    ],
    [
        "title" => "Multiplayer Game State",
        "content" => "Maintain consistent game state across players.\n- Use NetworkManager in Mirror.\n- Example: Sync card game state."
    ],
    [
        "title" => "Card Trading Mechanics",
        "content" => "Enable players to trade cards via blockchain.\n- Use NFT marketplaces like OpenSea.\n- Example: Trade rare cards in-game."
    ],
    [
        "title" => "Unity Canvas for Cards",
        "content" => "Unity Canvas creates responsive card UIs.\n- Supports drag-and-drop mechanics.\n- Example: Card layout in a multiplayer UI."
    ],
    [
        "title" => "Solidity for Smart Contracts",
        "content" => "Solidity is used for Ethereum smart contracts.\n- Defines card game rules.\n- Example: Contract for card battles."
    ],
    [
        "title" => "ChainSafe SDK",
        "content" => "ChainSafe SDK enhances Web3 game development.\n- Supports blockchain interactions.\n- Example: Use for card game transactions."
    ],
    [
        "title" => "Card Game Matchmaking",
        "content" => "Matchmaking ensures fair player pairing.\n- Use Photon’s matchmaking system.\n- Example: Match players by card deck strength."
    ],
    [
        "title" => "NFT Metadata in Unity",
        " SAWcontent" => "Store card attributes in NFT metadata.\n- Fetch via Web3 SDKs.\n- Example: Display card stats from metadata."
    ],
    [
        "title" => "Unity Matchmaker",
        "content" => "Unity Matchmaker pairs players for multiplayer games.\n- Simplifies lobby creation.\n- Example: Create a card game lobby."
    ],
    [
        "title" => "Blockchain Scalability",
        "content" => "Scalability is key for blockchain card games.\n- Use layer-2 solutions for high volume.\n- Example: Polygon for fast transactions."
    ],
    [
        "title" => "Card Game AI Opponents",
        "content" => "AI opponents enhance single-player modes.\n- Use in multiplayer for practice.\n- Example: AI for card game tutorials."
    ],
    [
        "title" => "Unity Networking APIs",
        "content" => "Unity’s networking APIs enable multiplayer.\n- Support for UDP-based communication.\n- Example: Sync card plays via UNet."
    ],
    [
        "title" => "Card Visual Effects",
        "content" => "Visual effects enhance card game appeal.\n- Use Unity Particle System.\n- Example: Card flip animations."
    ],
    [
        "title" => "Blockchain Game Security",
        "content" => "Secure blockchain games with encryption.\n- Protect player assets and data.\n- Example: Secure wallet connections."
    ],
    [
        "title" => "Card Game Leaderboards",
        "content" => "Leaderboards track player rankings.\n- Store on blockchain for transparency.\n- Example: Ethereum-based leaderboard."
    ],
    [
        "title" => "Multiplayer Latency",
        "content" => "Minimize latency for smooth multiplayer.\n- Use Photon Cloud for low ping.\n- Example: Real-time card game sync."
    ],
    [
        "title" => "Card Collection System",
        "content" => "Manage player card collections.\n- Store on blockchain as NFTs.\n- Example: Display owned cards in-game."
    ],
    [
        "title" => "Unity Web3 Libraries",
        "content" => "Web3 libraries bridge Unity and blockchain.\n- Examples: Moralis, Alchemy SDK.\n- Example: Fetch NFT cards with Alchemy."
    ],
    [
        "title" => "Card Game Tournaments",
        "content" => "Host tournaments for multiplayer card games.\n- Use Photon for large-scale events.\n- Example: 100-player card tournament."
    ],
    [
        "title" => "Blockchain Gas Optimization",
        "content" => "Optimize gas usage for blockchain transactions.\n- Batch transactions for efficiency.\n- Example: Batch card minting."
    ],
    [
        "title" => "Card Game Prototyping",
        "content" => "Prototype card games quickly in Unity.\n- Use prefabs for card objects.\n- Example: Test card mechanics."
    ],
    [
        "title" => "NFT Card Trading",
        "content" => "Enable NFT card trading in Unity.\n- Integrate with marketplaces like OpenSea.\n- Example: Trade cards via smart contract."
    ],
    [
        "title" => "Multiplayer Chat System",
        "content" => "Add chat to multiplayer card games.\n- Use Photon Chat for real-time messaging.\n- Example: In-game chat during matches."
    ],
    [
        "title" => "Card Game Balancing",
        "content" => "Balance card stats for fair gameplay.\n- Test mechanics in multiplayer.\n- Example: Adjust card power levels."
    ],
    [
        "title" => "Unity Prefabs for Cards",
        "content" => "Use prefabs for reusable card objects.\n- Simplifies multiplayer syncing.\n- Example: Prefab for card visuals."
    ],
    [
        "title" => "Blockchain Interoperability",
        "content" => "Support multiple blockchains in Unity.\n- Use Moralis for cross-chain.\n- Example: Cards on Ethereum and Polygon."
    ],
    [
        "title" => "Card Game Tutorials",
        "content" => "Tutorials teach players game mechanics.\n- Use Unity UI for guides.\n- Example: Interactive card game tutorial."
    ],
    [
        "title" => "Multiplayer Error Handling",
        "content" => "Handle network errors in multiplayer.\n- Ensure game state consistency.\n- Example: Reconnect after disconnect."
    ],
    [
        "title" => "NFT Card Rarity",
        "content" => "Define rarity levels for NFT cards.\n- Store in smart contract metadata.\n- Example: Rare card with high value."
    ],
    [
        "title" => "Card Game Analytics",
        "content" => "Track player behavior with analytics.\n- Use Unity Analytics for insights.\n- Example: Monitor card usage stats."
    ],
    [
        "title" => "Blockchain Wallet Security",
        "content" => "Secure player wallets in blockchain games.\n- Use 2FA and encryption.\n- Example: MetaMask with 2FA."
    ],
    [
        "title" => "Card Game Match History",
        "content" => "Store match history for players.\n- Use blockchain for transparency.\n- Example: Record card game outcomes."
    ],
    [
        "title" => "Multiplayer Scalability",
        "content" => "Scale multiplayer games for many players.\n- Use Photon Cloud for large games.\n- Example: 1000-player card game."
    ],
    [
        "title" => "Card Game Monetization",
        "content" => "Monetize card games with NFTs and tokens.\n- Offer rare cards for sale.\n- Example: Sell limited-edition NFTs."
    ],
    [
        "title" => "Unity Particle Effects",
        "content" => "Particle effects enhance card visuals.\n- Use for card play animations.\n- Example: Sparkle effect on rare cards."
    ],
    [
        "title" => "Blockchain Transaction Speed",
        "content" => "Fast blockchains improve game performance.\n- Use Algorand for quick trades.\n- Example: Instant card transactions."
    ],
    [
        "title" => "Card Game Community",
        "content" => "Build a community for card game players.\n- Use Discord for engagement.\n- Example: Host card game events."
    ],
    [
        "title" => "Multiplayer Game Modes",
        "content" => "Offer various multiplayer modes.\n- Examples: 1v1, 2v2, tournaments.\n- Example: 2v2 card game mode."
    ],
    [
        "title" => "NFT Card Art",
        "content" => "Create unique art for NFT cards.\n- Store art on IPFS for decentralization.\n- Example: Hand-drawn card visuals."
    ],
    [
        "title" => "Unity Shader Graph",
        "content" => "Shader Graph enhances card visuals.\n- Create custom card effects.\n- Example: Glowing card borders."
    ],
    [
        "title" => "Blockchain Game Testing",
        "content" => "Test blockchain games on testnets.\n- Use Sepolia for Ethereum testing.\n- Example: Test card minting."
    ],
    [
        "title" => "Card Game Feedback",
        "content" => "Collect player feedback for improvements.\n- Use in-game surveys.\n- Example: Feedback on card balance."
    ],
    [
        "title" => "Multiplayer Voice Chat",
        "content" => "Add voice chat to multiplayer games.\n- Use Vivox for Unity integration.\n- Example: Voice chat in card matches."
    ],
    [
        "title" => "Card Game Progression",
        "content" => "Implement progression systems.\n- Reward players with cards or tokens.\n- Example: Unlock cards via levels."
    ],
    [
        "title" => "Blockchain Game Updates",
        "content" => "Regularly update blockchain games.\n- Fix smart contract bugs.\n- Example: Patch card trading issues."
    ],
    [
        "title" => "Card Game Accessibility",
        "content" => "Make card games accessible.\n- Support colorblind modes.\n- Example: High-contrast card visuals."
    ],
    [
        "title" => "Multiplayer Fairness",
        "content" => "Ensure fair multiplayer gameplay.\n- Prevent cheating with server authority.\n- Example: Server-side card validation."
    ],
    [
        "title" => "NFT Card Auctions",
        "content" => "Host auctions for rare NFT cards.\n- Use smart contracts for bidding.\n- Example: Auction a legendary card."
    ],
    [
        "title" => "Unity Timeline for Cards",
        "content" => "Use Timeline for card animations.\n- Create cinematic card reveals.\n- Example: Animated card draw sequence."
    ],
    [
        "title" => "Blockchain Game Marketing",
        "content" => "Market blockchain card games effectively.\n- Use social media and Discord.\n- Example: Promote NFT card sales."
    ],
    [
        "title" => "Card Game Sound Design",
        "content" => "Sound effects enhance card games.\n- Use Unity Audio for card sounds.\n- Example: Card flip sound effect."
    ],
    [
        "title" => "Multiplayer Game Hosting",
        "content" => "Host multiplayer games on servers.\n- Use Unity Multiplay for hosting.\n- Example: Host card game servers."
    ],
    [
        "title" => "NFT Card Packs",
        "content" => "Sell NFT card packs in-game.\n- Randomize cards via smart contracts.\n- Example: Buy a 5-card NFT pack."
    ],
    [
        "title" => "Unity Addressables",
        "content" => "Addressables manage card assets efficiently.\n- Reduce memory usage in multiplayer.\n- Example: Load card textures on demand."
    ],
    [
        "title" => "Blockchain Game Ethics",
        "content" => "Consider ethics in blockchain games.\n- Ensure fair monetization.\n- Example: Transparent NFT drop rates."
    ],
    [
        "title" => "Card Game Localization",
        "content" => "Localize card games for global players.\n- Support multiple languages.\n- Example: Translate card descriptions."
    ],
    [
        "title" => "Multiplayer Game Debugging",
        "content" => "Debug multiplayer issues in Unity.\n- Use logs for network errors.\n- Example: Debug card sync issues."
    ],
    [
        "title" => "NFT Card Evolution",
        "content" => "Allow NFT cards to evolve.\n- Update metadata via smart contracts.\n- Example: Evolve a card to a higher tier."
    ]
];
?>