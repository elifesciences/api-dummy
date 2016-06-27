<?php

use Crell\ApiProblem\ApiProblem;
use eLife\Api\Blocks\Image as ImageBlock;
use eLife\Api\Blocks\Paragraph;
use eLife\Api\Blocks\Section;
use eLife\Api\Blocks\YouTube;
use eLife\Api\Experiment;
use eLife\Api\ExperimentNotFound;
use eLife\Api\InMemoryExperiments;
use eLife\Api\Serializer\ExperimentNormalizer;
use Negotiation\Accept;
use Negotiation\Negotiator;
use Silex\Application;
use Silex\Provider\SerializerServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

require_once __DIR__ . '/../vendor/autoload.php';

$experiments = [
    new Experiment(
        1,
        'What is Manuscripts?',
        DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2016-01-11 16:53:00'),
        [
            'alt' => '',
            'sizes' => [
                '2:1' => [
                    '900' => 'https://placehold.it/900x450',
                    '1800' => 'https://placehold.it/1800x900'
                ],
                '16:9' => [
                    '250' => 'https://placehold.it/250x141',
                    '500' => 'https://placehold.it/500x281'
                ],
                '1:1' => [
                    '70' => 'https://placehold.it/70x70',
                    '140' => 'https://placehold.it/140x140'
                ],
            ],
        ],
        [
            new Paragraph('It is a tool I created based on my own writing experiences during during my PhD research at the Sanger Institute in Cambridge.'),
            new Paragraph('In essence, Manuscripts is a word processor re-imagined to take a lot of mechanical formatting work off the author\'s shoulders, so he or she can focus on the actual paper. We top the editing features with a fully version-controlled file format and flexible import and export options.'),
            new Paragraph('The central solution we provide is helping an author focus on the substance of their story, as opposed to mechanical tasks that distract both the author and the publisher who ultimately receives the paper. Manuscripts helps authors to plan, navigate and edit a complex paper in a way that avoids them getting lost in the document and having to jump out of a natural writing flow to external tools that talk a different "language”.'),
            new Paragraph('Our design packs in an unprecedented range of functionality, including multi-figure panels, table editing and equations, as well as a top-of-the-line citation workflow, all in one unified experience.'),
            new Paragraph('We also include over 1,000 journal-specific manuscript templates to act as a starting point for writing and are seeking new publishing partners to build more. Manuscript templates inform many factors besides the obvious, such as maximum word counts and acceptable figure formats which, among other details, are enforced by the app.'),
            new Paragraph('The styling used in the document can be exported to Word, LaTeX and Markdown formats. In a way, Manuscripts helps both the author and publisher by guaranteeing a certain minimum technical quality to the documents, without users needing to understand paragraph styling or, for instance, how to produce clean typesetting markup in LaTeX form.'),
            new ImageBlock('', 'https://cdn.elifesciences.org/images/news/manuscripts-figure1.png',
                'Manuscripts at a glance'),
            new Section(
                'Manuscripts is an opinionated writing tool',
                new Paragraph('We are big believers in building approachable, easy-to-use tools because we think that researchers really value their time and are a conservative audience in picking up new workflows and tools. I think this is often under-appreciated in the publishing world.'),
                new ImageBlock('', 'https://cdn.elifesciences.org/images/news/manuscripts_fig2.png',
                    'Manuscripts includes over 1,000 manuscript templates that help authors get started with a complex writing project.'),
                new Paragraph('Compared to your typical word processor, Manuscripts shuns half a dozen ribbons and most character styling options available. This is because we think that good writing is all about consistency. A visually consistent, and dare I say beautiful, representation of your writing on the screen really helps you to maintain focus on the consistency of your writing during the entire process.'),
                new Paragraph('This is not to say Manuscripts is feature limited in terms of styling the document. It’s actually really powerful. However, a lot of this power is hidden away in the paragraph styling that is automatically applied to different parts of the document. For instance, headings are never pieces of bold text in Manuscripts, and you cannot space items out with tabs or extra linefeeds. We feel these kinds of formatting tricks are counterproductive for the author and also the publisher that receives the document, so we don’t provide them. Instead, we automatically prepare the visual representation of a document as much as possible. This is a task that computers are good at, just as long as the document is structured well, and it lets the author focus on formulating an argument – which is what they are good at.'),
                new Paragraph('To see how easy Manuscripts is to get started with, have a look at our intro video:'),
                new YouTube('-9JVFCL0fvk', 960, 720)
            ),
            new Section(
                'Referencing re-imagined',
                new Paragraph('An academic writing workflow typically involves using a citation tool that “sends” formatted reference data to the writing tool, to follow a specific citation style. The problem with this kind of workflow is that the reference metadata easily becomes an afterthought at the writing tool end, and the manuscript becomes locked into a particular reference management tool. This makes collaboration hard, and it also leads into situations where one is no longer easily able to reformat references: the metadata is auxiliary and can get lost, leaving behind only a particular visual representation of itself in the document.'),
                new Paragraph('We really want to avoid both of these problems. Manuscripts is therefore capable of formatting reference data itself so that the user has the freedom to reformat it. It also allows authors to use a mixture of citation tools in a collaborative process. In that sense, Manuscripts contains some of the logic that usually resides in a reference manager. It isn’t however trying to be a full-blown reference manager – instead, it includes an entirely open interface to work with external citation tools.'),
                new Paragraph('At the 1.0 launch, Manuscripts works in a highly integrated way with the Papers reference manager, in part to prove how much the deep citation tool integration can help an author become more productive. However, we also built support for importing bibliography data from essentially all key bibliography file formats (BibTeX, EndNote XML and RIS, among many others), and there\'s a built-in citation tool included for inserting citations. This means that you can already use it together with pretty much any given reference manager of your choice. We are also already working with external parties to integrate other reference management tools closely with the app.')
            ),
            new Section(
                'Offline first, but built for collaboration.',
                new Paragraph('Manuscripts at 1.0 is a personal, fully offline writing tool for Mac computers. We have gone with this design because a product, like writing a paper, requires focus. We have focused on proving the concept and building something beautiful and usable, allowing us to iterate very quickly according to early adopter feedback (we have been shipping beta updates on an almost daily basis, for instance).'),
                new Paragraph('The tremendous response we have received has really validated this choice: we have an extremely enthusiastic group of early adopters who spread the word about us, and this is what we are going to grow the business from. That being said, Mac is really just the beginning for us. We spent a good three years developing the product, and in part that is because we wanted to begin with building the technology into a form that can be ported to the web and other desktop platforms, and perhaps even the iPad.'),
                new Paragraph('We believe strongly that scholarly authors will continue to care about a fully offline writing experience, where they are in full ownership and control over their own manuscripts. The more substantial the writing project, the more likely that offline uses become key to real-world productivity. In this sense, the present breed of collaborative scientific writing tools all require an author to make a substantial compromise between personal productivity and the need to write collaboratively, and we think we will be able to offer something unique in that respect. Indeed, the next big thing you can expect from us is a solution to collaborative writing. To give you a hint, the Manuscripts documents are already fully version-controlled in part because of upcoming collaboration features.')
            )
        ],
        'Manuscripts is a writing tool for scholarly documents: it helps with the entire process of writing up complex work, from outlining the paper to the editing, proofreading and publishing stages, all from within one beautiful experience.',
        false
    ),
    new Experiment(
        2,
        'Toward publishing reproducible computation with Binder',
        DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2016-05-13 16:25:00'),
        [
            'alt' => '',
            'sizes' => [
                '2:1' => [
                    '900' => 'https://placehold.it/900x450',
                    '1800' => 'https://placehold.it/1800x900'
                ],
                '16:9' => [
                    '250' => 'https://placehold.it/250x141',
                    '500' => 'https://placehold.it/500x281'
                ],
                '1:1' => [
                    '70' => 'https://placehold.it/70x70',
                    '140' => 'https://placehold.it/140x140'
                ],
            ],
        ],
        [
            new Paragraph('Modern science depends on data analysis. From neuroscience to genomics, to cancer research, scientific conclusions are often several stages removed from raw data, and reflect extensive data processing and statistical analyses.'),
            new Paragraph('Yet in the traditional academic paper, we can only show a small sample of raw data, and report just a few of many possible summary statistics. We have to describe our analyses in compact paragraphs of plain text sprinkled with equations — an opaque starting point when trying to reproduce an analysis. Data and code, if shared at all, are appended to the paper as an afterthought, without ensuring that they are easy to reuse.'),
            new Paragraph('Why does this matter? Scientific progress depends on replicating and validating the work of others. And replicating what someone else has done is often the starting point for scientific collaboration.'),
            new Paragraph('Several open-source tools can be used to help address the challenges of sharing and reproducing scientific analyses. The <a href="http://jupyter.org/">Jupyter notebook</a> is a coding environment that runs in a web browser and lets users create computational “narrative documents” that combine code, data, figures, and text in a single interactive, executable document. These notebooks are easy to write, support many programming languages, and are already being used in science, journalism, and education.'),
            new ImageBlock('', 'https://cdn.elifesciences.org/images/labs/binder-post-juptyer-notebook.png'),
            new Paragraph('GitHub is a website for collaborative code development, built on top of the version-control system git. GitHub makes it easy to track changes to code over time, especially when multiple contributors are working on the same project. Putting data, code, and notebooks into a GitHub “repository” is a terrific way to share and organize scientific analyses.'),
            new ImageBlock('', 'https://cdn.elifesciences.org/images/labs/binder-post-github-repository.png'),
            new Paragraph('But just providing our code, data, and notebooks alongside a paper isn’t enough — what ran on my machine might not run on yours. We can share our computer configurations, but setting up a new machine the exact same way can be challenging and unreliable.'),
            new Paragraph('We designed Binder to make it as easy as possible to go straight from a paper to an interactive version of an analysis.'),
            new Section(
                'How it works',
                new Paragraph('To use Binder, you only have to put the code, data, and Jupyter notebooks that you are already using for analysis on your machine into a GitHub repository, and provide Binder with the link.'),
                new ImageBlock('', 'https://cdn.elifesciences.org/images/labs/binder-post-binder-homepage.png'),
                new Paragraph('Binder will then build an executable environment that contains all the necessary dependencies to run your code, and can be launched by clicking a link in your web browser. Now, with one click, anyone can immediately inspect the raw data, recompute a statistic, regenerate a figure, and perform arbitrary interactive analyses.'),
                new Paragraph('To make this work, Binder uses existing, robust tools wherever possible. Along with Jupyter and GitHub, Binder leverages two open-source projects under the hood to manage computational environments — Docker builds the environments from a project’s dependencies, and Kubernetes schedules resources for these environments across a cloud compute cluster.'),
                new Paragraph('A key use case for Binder is sharing analyses alongside traditional journal publications, and a few great examples of that already exist. A recent paper on neural coding in the somatosensory cortex by Sofroniew et al. in eLife used Binder to share data and analyses of neural recordings. Another example is a recent paper in Nature by Li et al. on robustness to perturbation in neural circuits, which used Binder to share simulation results from a computational model. Notebooks demonstrating the discovery of gravitational waves from the LIGO group were turned into a Binder, which has been by far our most popular example.'),
                new ImageBlock('', 'https://cdn.elifesciences.org/images/labs/binder-post-ligo-example.png'),
                new Paragraph('We’ve also seen Binder used in domains we didn’t expect. Outside of science publications, Binder has been used to make analyses for news stories more reproducible, and even to make an entire book on data science executable. It’s also become a popular way to run courses or tutorial sessions, because students can launch tutorials straight from their web browsers, without wasting precious time configuring dependencies. Physicists at CERN use it to showcase demos of their ROOT analysis framework.')
            ),
            new Section(
                'Next steps',
                new Paragraph('With more than 1200 reproducible environments already built by its users, Binder has proven useful and we’re excited by its potential — but much work remains, both on Binder itself and the underlying technologies, especially for making it better suited to scientific publishing.'),
                new Paragraph('Here are a couple of directions we’re excited about:'),
                new Paragraph('We currently maintain a public Binder deployment, hosted by our lab at HHMI Janelia Research Campus and running on Google Compute Engine, designed for open source and open science. But we’ve recently made it easy for others to deploy custom versions of Binder on their own compute infrastructure. We hope this can provide a way for publishers to deploy and host Binders with guaranteed availability for their readers.'),
                new Paragraph('We currently recommend users put data in their GitHub repositories, but git was designed to keep track of code, not data, which often consists of heterogenous files in a variety of formats, rather than plain text. We are collaborating with the team behind a new peer-to-peer data sharing and versioning project called Dat, and hope to integrate it with Binder.'),
                new Paragraph('We want to work on new ways to integrate static papers and interactive notebooks. Even with Binder, these remain separate and very different kinds of documents. Ideally, we would have unified interfaces that give readers the option to engage with code and figures at whatever level of detail they desire.')
            )
        ],
        'eLife is looking to innovate in all aspects of scientific publishing to support excellence in science and we’re pleased to bring attention to a new and exciting open-source project called Binder, created by scientists at HHMI’s Janelia Research Campus.',
        false
    ),
    new Experiment(
        3,
        'Experiment 3',
        DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2016-04-29 17:44:12'),
        [
            'alt' => '',
            'sizes' => [
                '2:1' => [
                    '900' => 'https://placehold.it/900x450',
                    '1800' => 'https://placehold.it/1800x900'
                ],
                '16:9' => [
                    '250' => 'https://placehold.it/250x141',
                    '500' => 'https://placehold.it/500x281'
                ],
                '1:1' => [
                    '70' => 'https://placehold.it/70x70',
                    '140' => 'https://placehold.it/140x140'
                ],
            ],
        ],
        [
            new Paragraph('Paragraph 1'),
            new ImageBlock('', 'https://placekitten.com/600/300', 'Kitteh!'),
            new Paragraph('Paragraph 2'),
        ],
        null,
        false
    ),
    new Experiment(
        4,
        'Experiment 4',
        DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2016-04-29 17:44:12'),
        [
            'alt' => '',
            'sizes' => [
                '2:1' => [
                    '900' => 'https://placehold.it/900x450',
                    '1800' => 'https://placehold.it/1800x900'
                ],
                '16:9' => [
                    '250' => 'https://placehold.it/250x141',
                    '500' => 'https://placehold.it/500x281'
                ],
                '1:1' => [
                    '70' => 'https://placehold.it/70x70',
                    '140' => 'https://placehold.it/140x140'
                ],
            ],
        ],
        [
            new Paragraph('Paragraph 1'),
            new ImageBlock('', 'https://placekitten.com/600/300', 'Kitteh!'),
            new Paragraph('Paragraph 2'),
        ],
        null,
        false
    ),
    new Experiment(
        5,
        'Experiment 5',
        DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2016-04-29 17:44:12'),
        [
            'alt' => '',
            'sizes' => [
                '2:1' => [
                    '900' => 'https://placehold.it/900x450',
                    '1800' => 'https://placehold.it/1800x900'
                ],
                '16:9' => [
                    '250' => 'https://placehold.it/250x141',
                    '500' => 'https://placehold.it/500x281'
                ],
                '1:1' => [
                    '70' => 'https://placehold.it/70x70',
                    '140' => 'https://placehold.it/140x140'
                ],
            ],
        ],
        [
            new Paragraph('Paragraph 1'),
            new ImageBlock('', 'https://placekitten.com/600/300', 'Kitteh!'),
            new Paragraph('Paragraph 2'),
        ],
        null,
        false
    ),
];

$app = new Application();

$app->register(new SerializerServiceProvider());

$app['serializer.normalizers'] = $app->extend('serializer.normalizers',
    function () {
        return [new ExperimentNormalizer()];
    }
);

$app['experiments'] = function () use ($app, $experiments) {
    return new InMemoryExperiments($experiments);
};

$app['negotiator'] = function () {
    return new Negotiator();
};

$app->get('/labs-experiments', function (Request $request) use ($app) {
    $accepts = [
        'application/vnd.elife.labs-experiment-list+json; version=1'
    ];

    $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

    if (null === $type) {
        $type = new Accept($accepts[0]);
    }

    $version = (int) $type->getParameter('version');
    $type = $type->getType();

    $experiments = $app['experiments']->all();

    $page = $request->query->get('page', 1);
    $perPage = $request->query->get('per-page', 10);

    $content = [
        'total' => count($experiments),
        'items' => [],
    ];

    if ('asc' === $request->query->get('order', 'desc')) {
        $experiments = array_reverse($experiments);
    }

    $experiments = array_slice($experiments, ($page * $perPage) - $perPage, $perPage);

    if (0 === count($experiments) && $page > 1) {
        throw new NotFoundHttpException('No page ' . $page);
    }

    foreach ($experiments as $i => $experiment) {
        $content['items'][$i] = json_decode($app['serializer']->serialize($experiment, 'json',
            ['version' => $version, 'partial' => true]), true);
    }

    $headers = ['Content-Type' => sprintf('%s; version=%s', $type, $version)];

    if ($request->query->get('foo')) {
        $headers['Warning'] = '299 elifesciences.org "Deprecation: `foo` query string parameter will be removed, use `bar` instead"';
    }

    return new Response(
        json_encode($content, JSON_PRETTY_PRINT),
        Response::HTTP_OK,
        $headers
    );
});

$app->get('/labs-experiments/{number}',
    function (Request $request, int $number) use ($app) {
        try {
            $experiment = $app['experiments']->get($number);
        } catch (ExperimentNotFound $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        };

        $accepts = [
            'application/vnd.elife.labs-experiment+json; version=1'
        ];

        $type = $app['negotiator']->getBest($request->headers->get('Accept'), $accepts);

        if (null === $type) {
            $type = new Accept($accepts[0]);
        }

        $version = (int) $type->getParameter('version');
        $type = $type->getType();

        $experiment = $app['serializer']->serialize($experiment, 'json', ['version' => $version]);

        return new Response(
            json_encode(json_decode($experiment), JSON_PRETTY_PRINT),
            Response::HTTP_OK,
            ['Content-Type' => sprintf('%s; version=%s', $type, $version)]
        );
    })->assert('number', '[1-9][0-9]*');

$app->error(function (Throwable $e) {
    if ($e instanceof HttpExceptionInterface) {
        $status = $e->getStatusCode();
        $message = $e->getMessage();
        $extra = [];
    } else {
        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'Error';
        $extra = [
            'exception' => $e->getMessage(),
            'stacktrace' => $e->getTraceAsString()
        ];
    }

    $problem = new ApiProblem($message);

    foreach ($extra as $key => $value) {
        $problem[$key] = $value;
    }

    return new Response(
        json_encode(json_decode($problem->asJson()), JSON_PRETTY_PRINT),
        $status,
        ['Content-Type' => 'application/problem+json']
    );
});

$app->run();
