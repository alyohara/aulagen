<?php

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\ContentStatus;
use App\Enums\CourseStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\ModuleType;
use App\Enums\UserRole;
use App\Jobs\ProcessDocument;
use App\Models\Activity;
use App\Models\Bibliography;
use App\Models\Course;
use App\Models\Document;
use App\Models\Question;
use App\Models\User;
use App\Services\DocumentProcessor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@aulagen.test'],
            ['name' => 'Administración', 'password' => Hash::make('password'), 'role' => UserRole::Admin, 'is_active' => true, 'email_verified_at' => now()],
        );

        $teacher = User::updateOrCreate(
            ['email' => 'docente@aulagen.test'],
            ['name' => 'Lucía Ferreyra', 'password' => Hash::make('password'), 'role' => UserRole::Teacher, 'is_active' => true, 'email_verified_at' => now(), 'title' => 'Prof. de Algoritmos', 'institution' => 'Universidad Tecnológica Nacional'],
        );

        User::updateOrCreate(
            ['email' => 'alumno@aulagen.test'],
            ['name' => 'Martín Ojeda', 'password' => Hash::make('password'), 'role' => UserRole::Student, 'is_active' => true, 'email_verified_at' => now()],
        );

        $course = Course::updateOrCreate(
            ['slug' => 'algoritmos-y-estructuras-de-datos'],
            [
                'owner_id' => $teacher->id,
                'name' => 'Algoritmos y Estructuras de Datos',
                'description' => 'Ciclo lectivo completo para analizar, implementar y comparar algoritmos y estructuras de datos fundamentales: desde la notación asintótica hasta listas, árboles, ordenamiento, grafos y tablas hash.',
                'institution' => 'Universidad Tecnológica Nacional',
                'career' => 'Ing. en Informática',
                'course_year' => '1.º año',
                'duration' => '16 semanas',
                'modality' => 'Presencial con material digital',
                'objectives' => "Analizar la eficiencia de un algoritmo usando notación asintótica.\nImplementar listas enlazadas, pilas, colas y árboles binarios de búsqueda.\nComparar estrategias de ordenamiento y seleccionar la adecuada según el caso.\nRepresentar y recorrer grafos con BFS y DFS.\nAplicar tablas hash y resolver colisiones.",
                'program' => "Unidad 1: Introducción y análisis de algoritmos\nUnidad 2: Estructuras lineales\nUnidad 3: Árboles\nUnidad 4: Ordenamiento\nUnidad 5: Grafos\nUnidad 6: Hashing",
                'status' => CourseStatus::Published,
                'primary_color' => '#4f46e5',
                'published_at' => now()->subDays(30),
                'structure_generated_at' => now()->subDays(30),
                'meta' => [
                    'collaborators' => 'Prof. Diego Salas, Prof. Ana Rivas',
                    'glossary' => [
                        ['term' => 'Algoritmo', 'definition' => 'Secuencia finita e incondicional de instrucciones que resuelve un problema determinado.'],
                        ['term' => 'Complejidad temporal', 'definition' => 'Crecimiento del tiempo de ejecución respecto del tamaño de la entrada, expresado con notación asintótica.'],
                        ['term' => 'Estabilidad (algoritmos de ordenamiento)', 'definition' => 'Propiedad de conservar el orden relativo de los elementos con claves iguales.'],
                        ['term' => 'Nodo hoja', 'definition' => 'Nodo de un árbol que no tiene hijos.'],
                        ['term' => 'Tabla hash', 'definition' => 'Estructura que almacena pares clave-valor aplicando una función de hash para ubicar cada elemento.'],
                    ],
                ],
            ],
        );

        $course->collaborators()->syncWithoutDetaching([$admin->id => ['role' => 'collaborator']]);

        $settings = $course->ensureSettings();
        $settings->update([
            'ai_assistant_enabled' => true,
            'allow_external_knowledge' => false,
            'show_sources' => true,
            'show_progress' => true,
            'allow_downloads' => true,
            'enable_search' => true,
        ]);

        $course->bibliography()->delete();
        Question::where('course_id', $course->id)->delete();
        $bibliography = [
            ['kind' => 'principal', 'authors' => 'Cormen, T. H.; Leiserson, C. E.; Rivest, R. L.; Stein, C. L.', 'title' => 'Introducción a los algoritmos', 'edition' => '3.ª ed.', 'publisher' => 'McGraw-Hill', 'year' => '2009'],
            ['kind' => 'principal', 'authors' => 'Sedgewick, R.; Wayne, K.', 'title' => 'Algoritmos', 'edition' => '4.ª ed.', 'publisher' => 'Addison-Wesley', 'year' => '2011'],
            ['kind' => 'complementaria', 'authors' => 'Weiss, M. A.', 'title' => 'Data Structures and Algorithm Analysis in C++', 'edition' => '4.ª ed.', 'publisher' => 'Pearson', 'year' => '2014'],
            ['kind' => 'complementaria', 'authors' => 'Forouzan, B. A.', 'title' => 'Estructuras de datos usando C', 'edition' => '1.ª ed.', 'publisher' => 'McGraw-Hill', 'year' => '2004'],
            ['kind' => 'complementaria', 'authors' => 'Cormen, T. H. et al.', 'title' => 'Introduction to Algorithms (MIT OpenCourseWare 6.006)', 'url' => 'https://ocw.mit.edu/courses/6-006-introduction-to-algorithms-spring-2020/', 'year' => '2020'],
        ];

        foreach ($bibliography as $index => $entry) {
            $course->bibliography()->create(array_merge($entry, ['position' => $index + 1]));
        }

        $course->modules()->delete();
        $course->lessons()->delete();
        $course->activities()->delete();
        $modules = $this->structure($course);
        $this->seedModules($course, $modules);
        $this->seedActivities($course);
        $this->seedDocument($course, $teacher);
    }

    /** @return array<int, array{title: string, summary: string, lessons: array<int, array<string, mixed>>}> */
    private function structure(Course $course): array
    {
        return [
            [
                'title' => 'Unidad 1: Introducción y análisis de algoritmos',
                'summary' => 'Qué es un algoritmo, cómo medir su eficiencia y cómo leer notación asintótica.',
                'lessons' => [
                    [
                        'title' => '¿Qué es un algoritmo y por qué medirlo?',
                        'summary' => 'Definición, propiedades de un algoritmo correcto y motivación del análisis de eficiencia.',
                        'content' => <<<'HTML'
<p>Un <strong>algoritmo</strong> es una secuencia finita de instrucciones no ambiguas que, partiendo de unos datos de entrada, produce una solución para un problema. Cuatro propiedades lo definen como correcto: <strong>finitud</strong> (termina en un número finito de pasos), <strong>precisión</strong> (cada instrucción está definida sin ambigüedad), <strong>entradas</strong> y <strong>salidas</strong>.</p>
<h3>¿Por qué analizar un algoritmo?</h3>
<p>Dos programas pueden resolver el mismo problema y comportarse de manera completamente distinta ante una entrada grande. Analizar un algoritmo significa predecir:</p>
<ul>
<li><strong>Temporización:</strong> cuánto crece el tiempo de ejecución a medida que crece la entrada.</li>
<li><strong>Espacio:</strong> cuánta memoria adicional necesita.</li>
</ul>
<h3>Ejemplo: búsqueda lineal</h3>
<pre><code>buscar(A, n, x):
  para i desde 0 hasta n-1:
    si A[i] == x:
      devolver i
  devolver -1</code></pre>
<p>Si el elemento no está, el algoritmo recorre los <code>n</code> elementos: en el peor caso realiza <code>n</code> comparaciones. Si duplicamos <code>n</code>, duplicamos el trabajo: el costo crece de forma <strong>lineal</strong>.</p>
<blockquote><p><strong>Fuente:</strong> Cormen et al., Introducción a los algoritmos (3.ª ed.), Cap. 1 y 2.1.</p></blockquote>
HTML,
                    ],
                    [
                        'title' => 'Notación asintótica: O, Ω y Θ',
                        'summary' => 'Cotas superiores e inferiores de crecimiento: cómo clasificar algoritmos por su orden de crecimiento.',
                        'content' => <<<'HTML'
<p>La notación asintótica describe el crecimiento del costo de un algoritmo sin dependecer de la máquina ni de las constantes de implementación.</p>
<h3>Las tres notaciones</h3>
<ul>
<li><strong>O(g(n)) — cota superior:</strong> existe constante <code>c &gt; 0</code> y un <code>n₀</code> tal que <code>f(n) ≤ c·g(n)</code> para todo <code>n ≥ n₀</code>. Responde a «no es peor que».</li>
<li><strong>Ω(g(n)) — cota inferior:</strong> existe <code>c &gt; 0</code> tal que <code>f(n) ≥ c·g(n)</code> eventualmente. Responde a «no es mejor que».</li>
<li><strong>Θ(g(n)) — orden exacto:</strong> vale O y Ω a la vez: el crecimiento está acotado por ambos lados.</li>
</ul>
<h3>Regla práctica</h3>
<p>Se descartan constantes y términos de menor orden: <code>3n² + 100n + 50</code> es <code>Θ(n²)</code>. Comparar: <code>Θ(log n) &lt; Θ(n) &lt; Θ(n log n) &lt; Θ(n²) &lt; Θ(2ⁿ)</code>.</p>
<h3>Búsqueda binaria</h3>
<p>Sobre un arreglo ordenado, cada paso descarta la mitad: <code>T(n) = T(n/2) + 1</code>, lo que resuelve en <code>Θ(log n)</code> comparaciones en el peor caso, frente a <code>Θ(n)</code> de la búsqueda lineal.</p>
<blockquote><p><strong>Fuente:</strong> Cormen et al., Introducción a los algoritmos (3.ª ed.), Cap. 2.1, 2.2 y 3.</p></blockquote>
HTML,
                    ],
                ],
            ],
            [
                'title' => 'Unidad 2: Estructuras lineales',
                'summary' => 'Listas enlazadas, pilas y colas: estructuras donde el orden importa.',
                'lessons' => [
                    [
                        'title' => 'Listas enlazadas simples',
                        'summary' => 'Nodos, punteros, inserción al frente y al final, y comparación con los arreglos.',
                        'content' => <<<'HTML'
<p>Una <strong>lista enlazada simple</strong> es una secuencia de nodos; cada nodo guarda un dato y un puntero al siguiente. La lista se conoce a través de su cabeza (<code>head</code>).</p>
<pre><code>clase Nodo:
  dato
  siguiente = nulo</code></pre>
<h3>Operaciones y su costo</h3>
<ul>
<li><strong>Insertar al frente:</strong> se crean dos punteros y se reasigna la cabeza: <code>O(1)</code>.</li>
<li><strong>Insertar al final sin puntero a cola:</strong> hay que recorrer toda la lista: <code>O(n)</code>; con puntero a cola vuelve a ser <code>O(1)</code>.</li>
<li><strong>Búsqueda de un elemento por valor:</strong> <code>O(n)</code> (no hay acceso aleatorio).</li>
<li><strong>Eliminación conociendo el nodo previo:</strong> <code>O(1)</code>.</li>
</ul>
<h3>Lista enlazada vs. arreglo</h3>
<p>El arreglo permite acceso aleatorio en <code>O(1)</code> pero insertar en el medio obliga a desplazar elementos (<code>O(n)</code>). La lista enlazada no requiere desplazamientos pero no tiene acceso aleatorio. La elección depende del patrón de uso.</p>
<blockquote><p><strong>Fuente:</strong> Cormen et al., Introducción a los algoritmos (3.ª ed.), Cap. 10.2; Sedgewick y Wayne, Algoritmos (4.ª ed.), Cap. 3.3.</p></blockquote>
HTML,
                    ],
                    [
                        'title' => 'Pilas y colas: LIFO y FIFO',
                        'summary' => 'ADT pila y ADT cola, sus implementaciones y aplicaciones típicas.',
                        'content' => <<<'HTML'
<p>Pila y cola son <strong>estructuras de acceso restringido</strong> (ADT, abstract data types): solo se define un conjunto limitado de operaciones.</p>
<h3>Pila (LIFO)</h3>
<p>El último elemento en entrar es el primero en salir. Operaciones: <code>push(x)</code>, <code>pop()</code>, <code>peek()</code> y <code>isEmpty()</code>, todas en <code>O(1)</code>. Aplicaciones: deshacer acciones (undo), evaluación de expresiones, recorrido recursivo del navegador, backtracking.</p>
<h3>Cola (FIFO)</h3>
<p>El primero en entrar es el primero en salir. Operaciones: <code>enqueue(x)</code> y <code>dequeue()</code>, ambas en <code>O(1)</code>. Aplicaciones: buffers, impresión, planificación de procesos, recorrido en amplitud de grafos.</p>
<h3>Implementaciones</h3>
<ul>
<li><strong>Pila con arreglo dinámico:</strong> ampliación ocasional amortiza en <code>O(1)</code> por operación.</li>
<li><strong>Cola con lista doblemente enlazada:</strong> <code>O(1)</code> garantizado al frente y al final.</li>
</ul>
<blockquote><p><strong>Fuente:</strong> Sedgewick y Wayne, Algoritmos (4.ª ed.), Cap. 4.3 y 1.3; Weiss, Data Structures and Algorithm Analysis, Cap. 3.</p></blockquote>
HTML,
                    ],
                ],
            ],
            [
                'title' => 'Unidad 3: Árboles',
                'summary' => 'Estructuras jerárquicas: ABB, recorridos y balance.',
                'lessons' => [
                    [
                        'title' => 'Árboles binarios de búsqueda (ABB)',
                        'summary' => 'Invariantes de un ABB, inserción, búsqueda y eliminación.',
                        'content' => <<<'HTML'
<p>Un <strong>árbol binario de búsqueda</strong> es un árbol en el que, para cualquier nodo <code>x</code>, todos los valores de su subárbol izquierdo son menores que <code>x</code> y todos los de su subárbol derecho son mayores. Esa invariante hace posible descartar mitades completas, como en la búsqueda binaria.</p>
<h3>Operaciones</h3>
<ul>
<li><strong>Búsqueda:</strong> desciende por una rama: <code>O(h)</code>, donde <code>h</code> es la altura.</li>
<li><strong>Inserción:</strong> busca la posición y agrega una hoja: <code>O(h)</code>.</li>
<li><strong>Eliminación:</strong> tres casos (hoja, un hijo, dos hijos: se reemplaza con el sucesor inorden): <code>O(h)</code>.</li>
</ul>
<h3>Altura y balance</h3>
<p>Con inserciones ordenadas el árbol degenera en una lista (<code>h = n</code>, <code>O(n)</code>). En un árbol balanceado, <code>h = Θ(log n)</code>. Los árboles AVL y rojo-negro garantizan balance para conservar <code>O(log n)</code>.</p>
<blockquote><p><strong>Fuente:</strong> Cormen et al., Introducción a los algoritmos (3.ª ed.), Cap. 12.2 y 12.3; Sedgewick y Wayne, Algoritmos (4.ª ed.), Cap. 3.2.</p></blockquote>
HTML,
                    ],
                    [
                        'title' => 'Recorridos: inorden, preorden y posorden',
                        'summary' => 'BFS y DFS aplicados a árboles, con implementación recursiva e iterativa.',
                        'content' => <<<'HTML'
<p>Un recorrido visita cada nodo exactamente una vez. En árboles binarios existen tres variantes según el momento en que se visita la raíz:</p>
<ul>
<li><strong>Preorden (raíz-izquierda-derecha):</strong> sirve para copiar un árbol o serializarlo.</li>
<li><strong>Inorden (izquierda-raíz-derecha):</strong> en un ABB devuelve los valores ordenados de menor a mayor.</li>
<li><strong>Posorden (izquierda-derecha-raíz):</strong> sirve para borrar un árbol o evaluar expresiones en notación posfija.</li>
</ul>
<h3>Costo</h3>
<p>Cada variante visita los <code>n</code> nodos y recorre <code>n-1</code> aristas: <code>Θ(n)</code>. La implementación recursiva es directa; la iterativa usa una pila explícita.</p>
<pre><code>inorden(nodo):
  si nodo es nulo: volver
  inorden(nodo.izquierdo)
  visitar(nodo.dato)
  inorden(nodo.derecho)</code></pre>
<blockquote><p><strong>Fuente:</strong> Cormen et al., Introducción a los algoritmos (3.ª ed.), Cap. 12.1 y 15.1.</p></blockquote>
HTML,
                    ],
                ],
            ],
            [
                'title' => 'Unidad 4: Ordenamiento',
                'summary' => 'Algoritmos cuadráticos y de divide-y-vencerás, con análisis de estabilidad y complejidad.',
                'lessons' => [
                    [
                        'title' => 'Ordenamiento por inserción y selección',
                        'summary' => 'Los dos algoritmos cuadráticos clásicos: invariante, código y casos de uso.',
                        'content' => <<<'HTML'
<h3>Ordenamiento por inserción</h3>
<p>Toma cada elemento y lo inserta en su lugar dentro del prefijo ya ordenado. Su invariante es «los primeros <code>i</code> elementos están ordenados».</p>
<pre><code>porInsercion(A):
  para i desde 1 hasta n-1:
    clave = A[i]
    j = i - 1
    mientras j >= 0 y A[j] > clave:
      A[j+1] = A[j]
      j = j - 1
    A[j+1] = clave</code></pre>
<ul>
<li><strong>Mejor caso:</strong> arreglo ya ordenado → <code>Θ(n)</code>.</li>
<li><strong>Peor caso:</strong> orden inverso → <code>Θ(n²)</code>.</li>
<li><strong>Estable:</strong> sí.</li>
</ul>
<h3>Ordenamiento por selección</h3>
<p>Busca repetidamente el mínimo del resto y lo coloca en la posición siguiente. Siempre es <code>Θ(n²)</code> (incluso en el mejor caso) y <strong>no es estable</strong>, pero escribe muy pocas veces en memoria.</p>
<h3>Cuándo usarlos</h3>
<p>Para <code>n</code> pequeño (≤ 20) o casi-ordenado, inserción es preferible. Los algoritmos de la Unidad 4.2 superan a ambos para entradas grandes.</p>
<blockquote><p><strong>Fuente:</strong> Cormen et al., Introducción a los algoritmos (3.ª ed.), Cap. 2.1 y 2.2; Sedgewick y Wayne, Algoritmos (4.ª ed.), Cap. 2.1.</p></blockquote>
HTML,
                    ],
                    [
                        'title' => 'Quicksort y mergesort',
                        'summary' => 'Divide y vencerás: partición, combinación, caso medio y peor caso.',
                        'content' => <<<'HTML'
<p>Ambos algoritmos aplican la estrategia <strong>divide-y-vencerás</strong> y alcanzan <code>Θ(n log n)</code> en promedio o en el caso medio.</p>
<h3>Mergesort</h3>
<p>Divide el arreglo por la mitad, ordena cada mitad y <em>fusiona</em> los arreglos ordenados. La fusión es <code>O(n)</code>; el recursivo <code>T(n) = 2T(n/2) + Θ(n)</code> resuelve a <code>Θ(n log n)</code> siempre, pero necesita <code>Θ(n)</code> de memoria auxiliar. Es estable.</p>
<h3>Quicksort</h3>
<p>Elige un pivote, particiona los menores a la izquierda y los mayores a la derecha, y sigue recursivamente.</p>
<ul>
<li><strong>Caso medio:</strong> la partición parte el arreglo a la mitad: <code>Θ(n log n)</code>.</li>
<li><strong>Peor caso:</strong> pivotes extremos con el arreglo ya ordenado: <code>Θ(n²)</code>. Se evita con pivote aleatorio o mediana de tres.</li>
<li><strong>Memoria:</strong> <code>O(log n)</code> de pila; es el ordenamiento de propósito general más usado en la práctica.</li>
</ul>
<blockquote><p><strong>Fuente:</strong> Cormen et al., Introducción a los algoritmos (3.ª ed.), Cap. 2.3 y 7; Sedgewick y Wayne, Algoritmos (4.ª ed.), Cap. 2.3.</p></blockquote>
HTML,
                    ],
                ],
            ],
            [
                'title' => 'Unidad 5: Grafos',
                'summary' => 'Representación de grafos y recorridos en amplitud y profundidad.',
                'lessons' => [
                    [
                        'title' => 'Representación de grafos: listas y matrices de adyacencia',
                        'summary' => 'Grafo dirigido o no dirigido, listas de adyacencia y matrices: cuándo conviene cada una.',
                        'content' => <<<'HTML'
<p>Un <strong>grafo</strong> <code>G = (V, E)</code> se compone de un conjunto de vértices y un conjunto de aristas; puede ser dirigido o no dirigido, con o sin pesos.</p>
<h3>Lista de adyacencia</h3>
<p>Un arreglo de <code>|V|</code> listas; la lista <code>v</code> contiene los vértices vecinos. Espacio: <code>O(V + E)</code>. Iterar los vecinos de <code>v</code> cuesta <code>O(deg(v))</code>. Ideal para grafos <strong>dispersos</strong>.</p>
<h3>Matriz de adyacencia</h3>
<p>Matriz <code>V × V</code> donde <code>A[u][v]</code> indica arista (o peso). Espacio: <code>O(V²)</code> siempre. Consultar si existe la arista <code>(u, v)</code>: <code>O(1)</code>. Ideal para grafos <strong>densos</strong> o con muchas consultas de existencia.</p>
<h3>Criterio de elección</h3>
<p>Si <code>E ≈ V²</code> la matriz gana en espacio (no desperdicia) y da <code>O(1)</code>; si <code>E ≪ V²</code> la lista ahorra memoria y tiempo de iteración.</p>
<blockquote><p><strong>Fuente:</strong> Cormen et al., Introducción a los algoritmos (3.ª ed.), Cap. 22.1; Sedgewick y Wayne, Algoritmos (4.ª ed.), Cap. 4.1.</p></blockquote>
HTML,
                    ],
                    [
                        'title' => 'Recorrido en amplitud (BFS) y profundidad (DFS)',
                        'summary' => 'Cola para BFS, pila para DFS, distancias mínimas y ciclos.',
                        'content' => <<<'HTML'
<h3>BFS — recorrido en amplitud</h3>
<p>Explora nivel por nivel usando una <strong>cola</strong>. Parte de un origen, encola sus vecinos y marca los visitados al encolar (no al desencolar, para evitar duplicados).</p>
<pre><code>BFS(G, origen):
  encolar(origen); visto[origen] = true
  mientras cola no vacía:
    u = desencolar()
    para cada v en adyacencia[u]:
      si no visto[v]: visto[v] = true; encolar(v)</code></pre>
<ul>
<li>Costo: <code>O(V + E)</code> con listas de adyacencia.</li>
<li>En un grafo no ponderado, BFS encuentra el <strong>camino más corto</strong> en número de aristas desde el origen.</li>
</ul>
<h3>DFS — recorrido en profundidad</h3>
<p>Avanza por una rama hasta el final (pila o recursión) y retrocede. También <code>O(V + E)</code>. Permite detectar ciclos, orden topológico y componentes conexas, y produce los tiempos de descubrimiento y finalización usados por los algoritmos de árboles abarcadores.</p>
<blockquote><p><strong>Fuente:</strong> Cormen et al., Introducción a los algoritmos (3.ª ed.), Cap. 22.2 y 22.3; Sedgewick y Wayne, Algoritmos (4.ª ed.), Cap. 4.1.</p></blockquote>
HTML,
                    ],
                ],
            ],
            [
                'title' => 'Unidad 6: Hashing',
                'summary' => 'Tablas hash, funciones de hash, manejo de colisiones y costos amortizados.',
                'lessons' => [
                    [
                        'title' => 'Tablas hash: funciones de hash y colisiones',
                        'summary' => 'Direcciónamiento abierto y encadenamiento, factores de carga y análisis de costos.',
                        'content' => <<<'HTML'
<p>Una <strong>tabla hash</strong> almacena pares clave-valor aplicando una función <code>h(clave)</code> que decide la posición (cubo) de cada elemento. Las operaciones básicas son <code>O(1)</code> en promedio.</p>
<h3>Función de hash</h3>
<p>Debe ser rápida y distribuir las claves uniformemente. Ejemplos: método de división <code>h(k) = k mod m</code> (elegir <code>m</code> primo ayuda) y la técnica de la multiplicación de Knuth.</p>
<h3>Colisiones</h3>
<ul>
<li><strong>Encadenamiento:</strong> cada cubo es una lista de pares; el factor de carga <code>α = n/m</code> crece y el costo pasa a <code>1 + α</code>. Simple y tolerante.</li>
<li><strong>Dirección abierta:</strong> ante colisión se buscan cubos libres con sondeo lineal, cuadrático o doble hash. Con <code>α &lt; 0,7</code> sigue siendo <code>O(1)</code>; nunca se debe eliminar con borrado simple (se usa borrado con marcado).</li>
</ul>
<h3>Análisis</h3>
<p>Con una función uniforme y <code>α</code> acotado, insertar, buscar y eliminar son <code>Θ(1)</code> en promedio; el peor caso degenera a <code>Θ(n)</code>, que se evita con rehash al superar el factor de carga de umbral.</p>
<blockquote><p><strong>Fuente:</strong> Cormen et al., Introducción a los algoritmos (3.ª ed.), Cap. 11.1 y 11.2; Sedgewick y Wayne, Algoritmos (4.ª ed.), Cap. 3.4.</p></blockquote>
HTML,
                    ],
                ],
            ],
        ];
    }

    private function seedModules(Course $course, array $structure): void
    {
        foreach ($structure as $moduleIndex => $moduleData) {
            $module = $course->modules()->create([
                'title' => $moduleData['title'],
                'slug' => Str::slug($moduleData['title']),
                'type' => ModuleType::Unit,
                'summary' => $moduleData['summary'],
                'position' => $moduleIndex + 1,
                'status' => 'active',
            ]);

            foreach ($moduleData['lessons'] as $lessonIndex => $lessonData) {
                $lesson = $course->lessons()->create([
                    'module_id' => $module->id,
                    'title' => $lessonData['title'],
                    'slug' => Str::slug($lessonData['title']),
                    'position' => $lessonIndex + 1,
                    'status' => ContentStatus::Published,
                    'content' => $lessonData['content'],
                    'summary' => $lessonData['summary'],
                    'sources' => $this->sourcesFromContent($lessonData['content']),
                    'related_document_ids' => [],
                    'is_ai_generated' => false,
                    'generated_at' => now()->subDays(25),
                    'approved_at' => now()->subDays(25),
                ]);

                $lesson->forceFill(['status' => ContentStatus::Published])->save();
            }
        }
    }

    private function seedActivities(Course $course): void
    {
        Question::where('course_id', $course->id)->delete();
        $course->activities()->delete();

        $activities = [
            [
                'title' => 'Autoevaluación: análisis y complejidad',
                'type' => ActivityType::Autoeval,
                'instructions' => 'Cuestionario de 4 preguntas sobre la Unit 1. Podés reintentarlo las veces que quieras.',
                'questions' => [
                    ['prompt' => 'La notación O(g(n)) representa:', 'options' => ['La cota inferior del crecimiento', 'La cota superior del crecimiento', 'El orden exacto del crecimiento', 'El tiempo exacto en milisegundos'], 'correct_answer' => 'La cota superior del crecimiento', 'explanation' => 'O es una cota superior: el algoritmo no crece más rápido que g(n).'],
                    ['prompt' => 'La búsqueda binaria sobre n elementos ordenados tiene complejidad:', 'options' => ['Θ(n)', 'Θ(log n)', 'Θ(n log n)', 'Θ(1)'], 'correct_answer' => 'Θ(log n)', 'explanation' => 'Cada comparación descarta la mitad del arreglo restante.'],
                    ['prompt' => 'Para 3n² + 100n + 50, su orden en notación Θ es:', 'options' => ['Θ(n)', 'Θ(n²)', 'Θ(n³)', 'Θ(log n)'], 'correct_answer' => 'Θ(n²)', 'explanation' => 'Se descartan constantes y los términos de menor orden.'],
                    ['prompt' => '¿Qué propiedad NO es requerida para que un algoritmo sea correcto?', 'options' => ['Finitud', 'Precisión de cada instrucción', 'Ejecutarse en menos de un segundo', 'Entradas y salidas definidas'], 'correct_answer' => 'Ejecutarse en menos de un segundo', 'explanation' => 'La corrección no depende del tiempo absoluto de ejecución, sino de finitud, precisión, entradas y salidas.'],
                ],
            ],
            [
                'title' => 'Cuestionario: estructuras lineales',
                'type' => ActivityType::Quiz,
                'instructions' => 'Preguntas de la Unidad 2 sobre listas, pilas y colas.',
                'questions' => [
                    ['prompt' => 'Insertar un nodo al frente de una lista enlazada simple tiene costo:', 'options' => ['O(1)', 'O(n)', 'O(log n)', 'O(n²)'], 'correct_answer' => 'O(1)', 'explanation' => 'Solo se reasigna el puntero de la cabeza.'],
                    ['prompt' => 'Una pila organiza sus elementos según el criterio:', 'options' => ['FIFO (primero en entrar, primero en salir)', 'LIFO (último en entrar, primero en salir)', 'Orden alfabético', 'Orden por prioridad'], 'correct_answer' => 'LIFO (último en entrar, primero en salir)', 'explanation' => 'La pila trabaja sobre el elemento más reciente: LIFO.'],
                    ['prompt' => 'En un arreglo, insertar un elemento en el medio tiene costo amortizado de:', 'options' => ['O(1)', 'O(log n)', 'O(n)', 'O(n log n)'], 'correct_answer' => 'O(n)', 'explanation' => 'Hay que desplazar todos los elementos posteriores.'],
                    ['prompt' => '¿Cuál NO es una operación típica del ADT cola?', 'options' => ['enqueue', 'dequeue', 'peek', 'push'], 'correct_answer' => 'push', 'explanation' => 'push pertenece al ADT pila; la cola usa enqueue y dequeue.'],
                ],
            ],
            [
                'title' => 'Cuestionario: árboles',
                'type' => ActivityType::Quiz,
                'instructions' => 'Preguntas de la Unidad 3 sobre ABB y recorridos.',
                'questions' => [
                    ['prompt' => 'En un ABB, los elementos del subárbol izquierdo de un nodo son:', 'options' => ['Todos mayores que el nodo', 'Todos menores que el nodo', 'Igual al nodo', 'Sin orden respecto del nodo'], 'correct_answer' => 'Todos menores que el nodo', 'explanation' => 'Es la invariante del ABB: izquierda menor, derecha mayor.'],
                    ['prompt' => 'El recorrido inorden sobre un ABB devuelve:', 'options' => ['Los elementos en orden ascendente', 'Los elementos en preorden', 'Los elementos de mayor a menor', 'Solo las hojas'], 'correct_answer' => 'Los elementos en orden ascendente', 'explanation' => 'Inorden = izquierda, raíz, derecha; en un ABB eso emite los valores ordenados.'],
                    ['prompt' => 'La altura de un ABB balanceado con n nodos es:', 'options' => ['Θ(1)', 'Θ(log n)', 'Θ(n)', 'Θ(n²)'], 'correct_answer' => 'Θ(log n)', 'explanation' => 'Un árbol balanceado divide el trabajo por nivel, como la búsqueda binaria.'],
                    ['prompt' => 'La complejidad de un recorrido completo sobre un árbol es:', 'options' => ['O(log n)', 'O(n)', 'O(n log n)', 'O(n²)'], 'correct_answer' => 'O(n)', 'explanation' => 'Cada nodo se visita exactamente una vez: Θ(n).'],
                ],
            ],
            [
                'title' => 'Flashcards: ordenamiento y grafos',
                'type' => ActivityType::Flashcards,
                'instructions' => 'Tarjetas de repaso rápido. Pensá la respuesta antes de dar vuelta la tarjeta.',
                'questions' => [],
                'payload' => [
                    'cards' => [
                        ['front' => '¿Por qué quicksort es Θ(n²) en el peor caso?', 'back' => 'Si el pivote es siempre el mínimo o el máximo (arreglo ordenado), las particiones quedan desbalanceadas: T(n) = T(n-1) + Θ(n).'],
                        ['front' => '¿Qué propiedad conserva mergesort pero no selección?', 'back' => 'La estabilidad: mergesort mantiene el orden relativo de elementos con claves iguales; el ordenamiento por selección no.'],
                        ['front' => 'BFS usa ¿cola o pila? ¿Y DFS?', 'back' => 'BFS usa cola (explora por niveles); DFS usa pila o recursión (explora en profundidad).'],
                        ['front' => 'Complejidad de BFS sobre lista de adyacencia', 'back' => 'O(V + E): cada vértice se encola una vez y cada arista se examina dos veces (no dirigido).'],
                    ],
                ],
            ],
        ];

        foreach ($activities as $index => $data) {
            $activity = $course->activities()->create([
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'type' => $data['type'],
                'instructions' => $data['instructions'],
                'status' => 'published',
                'position' => $index + 1,
                'payload' => $data['payload'] ?? [],
            ]);

            foreach ($data['questions'] as $questionIndex => $question) {
                Question::create([
                    'activity_id' => $activity->id,
                    'course_id' => $course->id,
                    'position' => $questionIndex + 1,
                    'type' => 'multiple_choice',
                    'prompt' => $question['prompt'],
                    'options' => $question['options'],
                    'correct_answer' => $question['correct_answer'],
                    'explanation' => $question['explanation'],
                    'points' => 1,
                ]);
            }
        }
    }

    private function seedDocument(Course $course, User $teacher): void
    {
        $existing = $course->documents()->where('original_name', 'Apuntes de clase - Unidad 1.txt')->first();
        if ($existing !== null) {
            return;
        }

        $content = <<<'TXT'
Apuntes de clase - Unidad 1: Introducción y análisis de algoritmos

Autor: Prof. Lucía Ferreyra - UTN

1. Definicion de algoritmo
Un algoritmo es una secuencia finita de instrucciones que recibe entradas, produce salidas y termina siempre. Debe ser preciso: cada paso se puede ejecutar sin interpretacion. Ejemplos cotidianos: la receta de cocina, el recorrido de un colectivo, la busqueda en un diccionario.

2. Analisis de eficiencia
El analisis puede ser empirico (medir tiempos en distintas entradas) o asintotico (deducir el orden de crecimiento). El analisis asintotico es independiente de la maquina y es el que usamos en la carrera. Se considera el peor caso cuando queremos garantias, el mejor caso cuando es representativo y el promedio cuando la distribucion de entradas es conocida.

3. Notacion asintotica
O(g(n)) es cota superior: el algoritmo no crece mas rapido que g. Omega(g(n)) es cota inferior. Theta(g(n)) indica el orden exacto. Para una funcion f(n) = 3n^2 + 100n + 50, el orden es Theta(n^2). Jerarquia de crecimiento tipica: 1 < log n < n < n log n < n^2 < 2^n.

4. Ejemplos de ordenamiento y busqueda
Busqueda lineal: peor caso Theta(n). Busqueda binaria sobre arreglo ordenado: Theta(log n). Insercion: mejor Theta(n), peor Theta(n^2). Seleccion: siempre Theta(n^2). Quicksort: promedio Theta(n log n), peor Theta(n^2). Mergesort: siempre Theta(n log n) con memoria Theta(n).

5. Puntos para el examen
- Saber derivar el costo de un bucle simple.
- Diferenciar O, Omega y Theta con un ejemplo.
- Justificar por que la busqueda binaria exige arreglo ordenado.
- Explicar por que quicksort es rapido en la practica a pesar de su peor caso.
TXT;

        $path = 'courses/'.$course->id.'/documents/'.now()->format('Ymd_His').'-'.uniqid().'-apuntes.txt';
        Storage::disk('local')->put($path, $content);

        $document = Document::create([
            'course_id' => $course->id,
            'uploaded_by' => $teacher->id,
            'original_name' => 'Apuntes de clase - Unidad 1.txt',
            'type' => DocumentType::Text,
            'mime_type' => 'text/plain',
            'size_bytes' => strlen($content),
            'disk' => 'local',
            'path' => $path,
            'status' => DocumentStatus::Pending,
        ]);

        try {
            app(DocumentProcessor::class)->process($document);
        } catch (\Throwable $e) {
            report($e);
            ProcessDocument::dispatch($document);
        }
    }

    /** @return array<int, string> */
    private function sourcesFromContent(string $content): array
    {
        preg_match_all('/<strong>Fuente:<\/strong>\s*([^<]+)/', $content, $matches);

        return array_values(array_unique(array_map('trim', $matches[1] ?? [])));
    }
}
