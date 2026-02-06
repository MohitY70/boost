---
name: ai-development
description: >-
  Develops AI-powered features with Laravel AI SDK. Activates when creating agents, generating
  images/audio, working with embeddings, creating AI tools, implementing RAG (retrieval-augmented
  generation), using structured output, streaming responses, working with vector databases,
  similarity search, reranking; or when the user mentions Laravel AI, AI agents, LLM, GPT,
  Claude, Anthropic, OpenAI, Gemini, Groq, xAI, AI tools, vector embeddings, semantic search,
  TTS (text-to-speech), STT (speech-to-text), transcription, or AI-powered features.
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Laravel AI Development

## When to Apply

Activate this skill when:

- Creating or modifying AI agents
- Generating images, audio, or transcriptions
- Working with embeddings and vector databases
- Implementing RAG or similarity search
- Creating AI tools for agents
- Testing AI features

## Documentation

Use `search-docs` for detailed Laravel AI SDK patterns and documentation.

## Installation

Laravel AI SDK can be installed via Composer:

@boostsnippet("Installing Laravel AI", "bash")
composer require laravel/ai
{{ $assist->artisanCommand('vendor:publish --provider="Laravel\Ai\AiServiceProvider"') }}
{{ $assist->artisanCommand('migrate') }}
@endboostsnippet

## Configuration

Configure AI provider credentials in `config/ai.php` or `.env`:

@boostsnippet("AI Provider Configuration", "env")
ANTHROPIC_API_KEY=
COHERE_API_KEY=
ELEVENLABS_API_KEY=
GEMINI_API_KEY=
OPENAI_API_KEY=
JINA_API_KEY=
XAI_API_KEY=
@endboostsnippet

## Agents

### Creating Agents

Create agents using the artisan command:

@boostsnippet("Create Agent Commands", "bash")
{{ $assist->artisanCommand('make:agent SalesCoach') }}
{{ $assist->artisanCommand('make:agent DataAnalyzer --structured') }}
@endboostsnippet

### Basic Agent Structure

@boostsnippet("Basic Agent Example", "php")
<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class SalesCoach implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are a sales coach analyzing transcripts.';
    }
}
@endboostsnippet

### Prompting Agents

@boostsnippet("Prompting Agents", "php")
use App\Ai\Agents\SalesCoach;

// Basic prompting
$response = (new SalesCoach)->prompt('Analyze this transcript...');
return (string) $response;

// Using make() for dependency injection
$response = SalesCoach::make(user: $user)
    ->prompt('Analyze this transcript...');

// Override provider/model/timeout
$response = (new SalesCoach)->prompt(
    'Analyze this transcript...',
    provider: 'anthropic',
    model: 'claude-haiku-4-5-20251001',
    timeout: 120,
);
@endboostsnippet

### Conversation Context

Implement `Conversational` interface for conversation history:

@boostsnippet("Conversational Agent", "php")
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\Message;
use App\Models\History;

class SalesCoach implements Agent, Conversational
{
    use Promptable;

    public function __construct(public User $user) {}

    public function messages(): iterable
    {
        return History::where('user_id', $this->user->id)
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->map(fn ($message) => new Message($message->role, $message->content))
            ->all();
    }
}
@endboostsnippet

### Automatic Conversation Storage

Use `RemembersConversations` trait for automatic conversation persistence:

@boostsnippet("Remember Conversations", "php")
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Conversational;

class SalesCoach implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    // Start new conversation
    $response = (new SalesCoach)->forUser($user)->prompt('Hello!');
    $conversationId = $response->conversationId;

    // Continue conversation
    $response = (new SalesCoach)
        ->continue($conversationId, as: $user)
        ->prompt('Tell me more.');
}
@endboostsnippet

### Structured Output

Implement `HasStructuredOutput` for schema-based responses:

@boostsnippet("Structured Output Agent", "php")
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\HasStructuredOutput;

class SalesCoach implements Agent, HasStructuredOutput
{
    use Promptable;

    public function schema(JsonSchema $schema): array
    {
        return [
            'feedback' => $schema->string()->required(),
            'score' => $schema->integer()->min(1)->max(10)->required(),
        ];
    }
}

// Access structured response
$response = (new SalesCoach)->prompt('Analyze...');
return $response['score'];
@endboostsnippet

### Attachments

Attach files to prompts:

@boostsnippet("Prompt Attachments", "php")
use Laravel\Ai\Files;

$response = (new SalesCoach)->prompt(
    'Analyze the attached transcript...',
    attachments: [
        Files\Document::fromStorage('transcript.pdf'),
        Files\Document::fromPath('/path/to/file.md'),
        Files\Image::fromStorage('photo.jpg'),
        $request->file('document'),
    ]
);
@endboostsnippet

### Streaming Responses

Stream agent responses for real-time output:

@boostsnippet("Streaming Responses", "php")
use App\Ai\Agents\SalesCoach;

// Return streaming response (SSE)
Route::get('/coach', function () {
    return (new SalesCoach)->stream('Analyze...');
});

// Callback when streaming completes
return (new SalesCoach)
    ->stream('Analyze...')
    ->then(function (StreamedAgentResponse $response) {
        // Handle complete response
    });

// Manual iteration
$stream = (new SalesCoach)->stream('Analyze...');
foreach ($stream as $event) {
    // Process each event
}

// Vercel AI SDK protocol
return (new SalesCoach)
    ->stream('Analyze...')
    ->usingVercelDataProtocol();
@endboostsnippet

### Broadcasting & Queueing

@boostsnippet("Broadcasting and Queueing", "php")
// Broadcast streamed events
$stream = (new SalesCoach)->stream('Analyze...');
foreach ($stream as $event) {
    $event->broadcast(new Channel('channel-name'));
}

// Queue agent operation with broadcasting
(new SalesCoach)->broadcastOnQueue(
    'Analyze...',
    new Channel('channel-name'),
);

// Queue agent prompt
(new SalesCoach)
    ->queue('Analyze...')
    ->then(function (AgentResponse $response) {
        // Handle response
    })
    ->catch(function (Throwable $e) {
        // Handle error
    });
@endboostsnippet

### Agent Configuration Attributes

@boostsnippet("Agent Configuration", "php")
use Laravel\Ai\Attributes\{MaxSteps, MaxTokens, Provider, Temperature, Timeout};
use Laravel\Ai\Attributes\{UseCheapestModel, UseSmartestModel};

#[MaxSteps(10)]
#[MaxTokens(4096)]
#[Provider('anthropic')]
#[Temperature(0.7)]
#[Timeout(120)]
class SalesCoach implements Agent
{
    use Promptable;
}

// Use cheapest or smartest models
#[UseCheapestModel]
class SimpleSummarizer implements Agent { }

#[UseSmartestModel]
class ComplexReasoner implements Agent { }
@endboostsnippet

## Tools

### Creating Tools

Create tools using artisan:

@boostsnippet("Create Tool", "bash")
{{ $assist->artisanCommand('make:tool RandomNumberGenerator') }}
@endboostsnippet

### Tool Implementation

@boostsnippet("Tool Example", "php")
<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class RandomNumberGenerator implements Tool
{
    public function description(): Stringable|string
    {
        return 'Generates cryptographically secure random numbers.';
    }

    public function handle(Request $request): Stringable|string
    {
        return (string) random_int($request['min'], $request['max']);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'min' => $schema->integer()->min(0)->required(),
            'max' => $schema->integer()->required(),
        ];
    }
}
@endboostsnippet

### Similarity Search Tool

Built-in tool for RAG and semantic search:

@boostsnippet("Similarity Search Tool", "php")
use App\Models\Document;
use Laravel\Ai\Tools\SimilaritySearch;

public function tools(): iterable
{
    return [
        // Simple usage
        SimilaritySearch::usingModel(Document::class, 'embedding'),

        // With options
        SimilaritySearch::usingModel(
            model: Document::class,
            column: 'embedding',
            minSimilarity: 0.7,
            limit: 10,
            query: fn ($query) => $query->where('published', true),
        ),

        // Custom closure
        new SimilaritySearch(using: function (string $query) {
            return Document::query()
                ->where('user_id', $this->user->id)
                ->whereVectorSimilarTo('embedding', $query)
                ->limit(10)
                ->get();
        }),

        // Custom description
        SimilaritySearch::usingModel(Document::class, 'embedding')
            ->withDescription('Search the knowledge base.'),
    ];
}
@endboostsnippet

### Provider Tools

Use native provider tools for web search, fetch, and file search:

@boostsnippet("Provider Tools", "php")
use Laravel\Ai\Providers\Tools\{WebSearch, WebFetch, FileSearch};
use Laravel\Ai\Providers\Tools\FileSearchQuery;

public function tools(): iterable
{
    return [
        // Web search (Anthropic, OpenAI, Gemini)
        (new WebSearch)->max(5)->allow(['laravel.com', 'php.net']),
        (new WebSearch)->location(city: 'New York', region: 'NY', country: 'US'),

        // Web fetch (Anthropic, Gemini)
        (new WebFetch)->max(3)->allow(['docs.laravel.com']),

        // File search in vector stores (OpenAI, Gemini)
        new FileSearch(stores: ['store_id']),
        new FileSearch(stores: ['store_1', 'store_2']),

        // With metadata filtering
        new FileSearch(stores: ['store_id'], where: [
            'author' => 'Taylor Otwell',
            'year' => 2026,
        ]),

        // Complex filtering
        new FileSearch(stores: ['store_id'], where: fn (FileSearchQuery $query) =>
            $query->where('author', 'Taylor Otwell')
                ->whereNot('status', 'draft')
                ->whereIn('category', ['news', 'updates'])
        ),
    ];
}
@endboostsnippet

## Images

Generate images with AI providers:

@boostsnippet("Image Generation", "php")
use Laravel\Ai\Image;

// Basic generation
$image = Image::of('A donut on the counter')->generate();
$rawContent = (string) $image;

// With options
$image = Image::of('A donut on the counter')
    ->quality('high')
    ->landscape()  // or ->square(), ->portrait()
    ->timeout(120)
    ->generate();

// With reference images
$image = Image::of('Update this photo in impressionist style.')
    ->attachments([
        Files\Image::fromStorage('photo.jpg'),
        Files\Image::fromPath('/path/to/photo.jpg'),
        Files\Image::fromUrl('https://example.com/photo.jpg'),
        $request->file('photo'),
    ])
    ->generate();

// Store image
$path = $image->store();
$path = $image->storeAs('image.jpg');
$path = $image->storePublicly();
$path = $image->storePubliclyAs('image.jpg');

// Queue image generation
Image::of('A donut on the counter')
    ->portrait()
    ->queue()
    ->then(function (ImageResponse $image) {
        $path = $image->store();
    });
@endboostsnippet

## Audio (TTS)

Generate audio from text:

@boostsnippet("Audio Generation", "php")
use Laravel\Ai\Audio;

// Basic generation
$audio = Audio::of('I love coding with Laravel.')->generate();
$rawContent = (string) $audio;

// With voice options
$audio = Audio::of('I love coding with Laravel.')
    ->female()  // or ->male()
    ->generate();

$audio = Audio::of('I love coding with Laravel.')
    ->voice('voice-id-or-name')
    ->generate();

// With instructions
$audio = Audio::of('I love coding with Laravel.')
    ->female()
    ->instructions('Said like a pirate')
    ->generate();

// Store audio
$path = $audio->store();
$path = $audio->storeAs('audio.mp3');
$path = $audio->storePublicly();
$path = $audio->storePubliclyAs('audio.mp3');

// Queue audio generation
Audio::of('I love coding with Laravel.')
    ->queue()
    ->then(function (AudioResponse $audio) {
        $path = $audio->store();
    });
@endboostsnippet

## Transcription (STT)

Generate transcripts from audio:

@boostsnippet("Transcription", "php")
use Laravel\Ai\Transcription;

// Generate transcript
$transcript = Transcription::fromPath('/path/to/audio.mp3')->generate();
$transcript = Transcription::fromStorage('audio.mp3')->generate();
$transcript = Transcription::fromUpload($request->file('audio'))->generate();

return (string) $transcript;

// With diarization (speaker segmentation)
$transcript = Transcription::fromStorage('audio.mp3')
    ->diarize()
    ->generate();

// Queue transcription
Transcription::fromStorage('audio.mp3')
    ->queue()
    ->then(function (TranscriptionResponse $transcript) {
        // Handle transcript
    });
@endboostsnippet

## Embeddings

Generate vector embeddings for semantic search:

@boostsnippet("Embeddings", "php")
use Illuminate\Support\Str;
use Laravel\Ai\Embeddings;

// Quick embedding generation
$embeddings = Str::of('Napa Valley has great wine.')->toEmbeddings();

// Multiple inputs
$response = Embeddings::for([
    'Napa Valley has great wine.',
    'Laravel is a PHP framework.',
])->generate();

$response->embeddings; // [[0.123, 0.456, ...], [0.789, 0.012, ...]]

// With options
$response = Embeddings::for(['Napa Valley has great wine.'])
    ->dimensions(1536)
    ->generate('openai', 'text-embedding-3-small');
@endboostsnippet

### Vector Database Queries

@boostsnippet("Vector Database Setup", "php")
// Migration
Schema::ensureVectorExtensionExists();

Schema::create('documents', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->vector('embedding', dimensions: 1536)->index();
    $table->timestamps();
});

// Model
protected function casts(): array
{
    return [
        'embedding' => 'array',
    ];
}
@endboostsnippet

@boostsnippet("Vector Similarity Search", "php")
use App\Models\Document;

// Simple similarity search
$documents = Document::query()
    ->whereVectorSimilarTo('embedding', $queryEmbedding, minSimilarity: 0.4)
    ->limit(10)
    ->get();

// With string query (auto-generates embedding)
$documents = Document::query()
    ->whereVectorSimilarTo('embedding', 'best wineries in Napa Valley')
    ->limit(10)
    ->get();

// Advanced vector queries
$documents = Document::query()
    ->select('*')
    ->selectVectorDistance('embedding', $queryEmbedding, as: 'distance')
    ->whereVectorDistanceLessThan('embedding', $queryEmbedding, maxDistance: 0.3)
    ->orderByVectorDistance('embedding', $queryEmbedding)
    ->limit(10)
    ->get();
@endboostsnippet

### Caching Embeddings

@boostsnippet("Embedding Cache Configuration", "php")
// config/ai.php
'caching' => [
    'embeddings' => [
        'cache' => true,
        'store' => env('CACHE_STORE', 'database'),
    ],
],

// Per-request caching
$response = Embeddings::for(['Napa Valley has great wine.'])
    ->cache()  // Default 30 days
    ->generate();

$response = Embeddings::for(['Napa Valley has great wine.'])
    ->cache(seconds: 3600)  // Custom duration
    ->generate();

// Stringable method
$embeddings = Str::of('Napa Valley has great wine.')->toEmbeddings(cache: true);
$embeddings = Str::of('Napa Valley has great wine.')->toEmbeddings(cache: 3600);
@endboostsnippet

## Reranking

Reorder documents by relevance:

@boostsnippet("Reranking", "php")
use Laravel\Ai\Reranking;

// Basic reranking
$response = Reranking::of([
    'Django is a Python web framework.',
    'Laravel is a PHP web application framework.',
    'React is a JavaScript library for building user interfaces.',
])->rerank('PHP frameworks');

$response->first()->document; // "Laravel is a PHP web application framework."
$response->first()->score;    // 0.95
$response->first()->index;    // 1 (original position)

// With limit
$response = Reranking::of($documents)
    ->limit(5)
    ->rerank('search query');

// Collection reranking
$posts = Post::all()->rerank('body', 'Laravel tutorials');
$reranked = $posts->rerank(['title', 'body'], 'Laravel tutorials');

// With closure
$reranked = $posts->rerank(
    fn ($post) => $post->title.': '.$post->body,
    'Laravel tutorials'
);

// With provider
$reranked = $posts->rerank(
    by: 'content',
    query: 'Laravel tutorials',
    limit: 10,
    provider: 'cohere'
);
@endboostsnippet

## Files & Vector Stores

### Storing Files

@boostsnippet("File Storage", "php")
use Laravel\Ai\Files\Document;
use Laravel\Ai\Files\Image;

// Store files with provider
$response = Document::fromPath('/path/to/document.pdf')->put();
$response = Document::fromStorage('document.pdf', disk: 'local')->put();
$response = Document::fromUrl('https://example.com/document.pdf')->put();
$response = Document::fromString('Hello, World!', 'text/plain')->put();
$response = Document::fromUpload($request->file('document'))->put();

$fileId = $response->id;

// Use stored file in prompts
$response = (new SalesCoach)->prompt(
    'Analyze the attached transcript...',
    attachments: [
        Files\Document::fromId($fileId),
    ]
);

// Retrieve file
$file = Document::fromId('file-id')->get();

// Delete file
Document::fromId('file-id')->delete();
@endboostsnippet

### Vector Stores

@boostsnippet("Vector Store Management", "php")
use Laravel\Ai\Stores;

// Create vector store
$store = Stores::create('Knowledge Base');

$store = Stores::create(
    name: 'Knowledge Base',
    description: 'Documentation and reference materials.',
    expiresWhenIdleFor: days(30),
);

$storeId = $store->id;

// Retrieve store
$store = Stores::get('store_id');

// Add files to store
$document = $store->add('file_id');
$document = $store->add(Document::fromId('file_id'));
$document = $store->add(Document::fromPath('/path/to/document.pdf'));
$document = $store->add(Document::fromStorage('manual.pdf'));
$document = $store->add($request->file('document'));

// Add with metadata for filtering
$store->add(Document::fromPath('/path/to/document.pdf'), metadata: [
    'author' => 'Taylor Otwell',
    'department' => 'Engineering',
    'year' => 2026,
]);

// Remove file from store
$store->remove('file_id');
$store->remove('file_id', deleteFile: true);  // Also delete from provider

// Delete store
Stores::delete('store_id');
$store->delete();
@endboostsnippet

## Failover

Automatic provider/model failover:

@boostsnippet("Failover Configuration", "php")
use App\Ai\Agents\SalesCoach;
use Laravel\Ai\Image;

// Agent with failover
$response = (new SalesCoach)->prompt(
    'Analyze this transcript...',
    provider: ['openai', 'anthropic'],
);

// Image generation with failover
$image = Image::of('A donut on the counter')
    ->generate(provider: ['gemini', 'xai']);
@endboostsnippet

## Testing

### Agent Testing

@boostsnippet("Testing Agents", "php")
use App\Ai\Agents\SalesCoach;

// Fake responses
SalesCoach::fake();
SalesCoach::fake(['First response', 'Second response']);
SalesCoach::fake(function (AgentPrompt $prompt) {
    return 'Response for: '.$prompt->prompt;
});

// Assertions
SalesCoach::assertPrompted('Analyze this...');
SalesCoach::assertPrompted(fn (AgentPrompt $prompt) => $prompt->contains('Analyze'));
SalesCoach::assertNotPrompted('Missing prompt');
SalesCoach::assertNeverPrompted();

// Queue assertions
SalesCoach::assertQueued('Analyze this...');
SalesCoach::assertQueued(fn (QueuedAgentPrompt $prompt) => $prompt->contains('Analyze'));
SalesCoach::assertNotQueued('Missing prompt');
SalesCoach::assertNeverQueued();

// Prevent stray prompts
SalesCoach::fake()->preventStrayPrompts();
@endboostsnippet

### Testing Other Features

@boostsnippet("Testing Images, Audio, Embeddings", "php")
use Laravel\Ai\{Image, Audio, Transcription, Embeddings, Reranking};
use Laravel\Ai\{Files, Stores};

// Images
Image::fake();
Image::fake([base64_encode($image1), base64_encode($image2)]);
Image::assertGenerated(fn ($prompt) => $prompt->contains('sunset'));
Image::assertNotGenerated('Missing prompt');

// Audio
Audio::fake();
Audio::assertGenerated(fn ($prompt) => $prompt->contains('Hello'));

// Transcriptions
Transcription::fake();
Transcription::assertGenerated(fn ($prompt) => $prompt->isDiarized());

// Embeddings
Embeddings::fake();
Embeddings::assertGenerated(fn ($prompt) => $prompt->contains('Laravel'));

// Reranking
Reranking::fake();
Reranking::assertReranked(fn ($prompt) => $prompt->limit === 5);

// Files
Files::fake();
Files::assertStored(fn ($file) => (string) $file === 'Hello, Laravel!');
Files::assertDeleted('file-id');

// Stores
Stores::fake();
Stores::assertCreated('Knowledge Base');
Stores::assertDeleted('store_id');

$store = Stores::get('store_id');
$store->assertAdded('file_id');
$store->assertRemoved('file_id');
@endboostsnippet

## Best Practices

### Security & Validation

- Always validate form data and run authorization checks in agent actions
- Use structured output for predictable responses
- Implement proper error handling for API failures

### Performance

- Cache embeddings for frequently used inputs
- Use `UseCheapestModel` for simple tasks
- Implement queueing for long-running operations
- Use streaming for better user experience

### Agent Design

- Keep agent instructions clear and specific
- Use tools to extend agent capabilities
- Implement conversation context for continuity
- Test agents thoroughly with various inputs

### Vector Search

- Create vector indexes for better performance
- Use appropriate similarity thresholds
- Implement RAG for accurate, context-aware responses
- Consider reranking for improved relevance

## Common Pitfalls

- Not publishing migrations before using conversation storage
- Forgetting to configure provider API keys
- Not implementing error handling for provider failures
- Using synchronous operations for time-consuming tasks
- Not utilizing structured output when predictable responses are needed
- Forgetting to add vector indexes for large datasets
- Not caching frequently generated embeddings
- Using incorrect vector dimensions in migrations

## Verification

1. Test agent responses with various prompts
2. Verify streaming works correctly
3. Check conversation persistence
4. Validate structured output schema
5. Test tool invocations
6. Verify vector similarity searches return relevant results
