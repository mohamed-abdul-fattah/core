<?php
declare(strict_types=1);

namespace Http;


use PHPUnit\Framework\TestCase;
use TypeRocket\Core\Injector;
use TypeRocket\Elements\Form;
use TypeRocket\Http\ApplicationRoutes;
use TypeRocket\Http\CustomRequest;
use TypeRocket\Http\Redirect;
use TypeRocket\Http\Route;
use TypeRocket\Http\RouteCollection;
use TypeRocket\Http\Routes;
use TypeRocket\Models\WPPost;

class RouteTest extends TestCase
{

    public function testMakeGetRoute()
    {
        $route = tr_route()->get()->match('/app-test')->do(function() {
            return 'Hi';
        });

        $this->assertTrue($route instanceof Route);
    }

    public function testRouteNewCollection()
    {
        tr_route()->get()->match('/app-test')->do(function() {
            return 'Hi';
        });

        $count = Injector::resolve(RouteCollection::class)->count();

        $route = new Route;

        $route->register(new class extends RouteCollection {});

        $count_new = Injector::resolve(RouteCollection::class)->count();

        $this->assertTrue($count_new == $count);
    }

    public function testRouteDoWithArg()
    {
        tr_route()->get()->match('about/([^\/]+)', ['id'])->do(function($id, RouteCollection $routes) {
            return [$id, $routes->count()];
        });

        $request = new CustomRequest([
            'path' => 'wordpress/about/1/',
            'method' => 'GET'
        ]);

        $route = (new Routes($request, [
            'root' => 'https://example.com/wordpress/'
        ], Injector::resolve(RouteCollection::class) ))->detectRoute();

        $map = resolve_method_args($route->match[1]->do,  $route->match[2]);
        $response = resolve_method_map($map);

        $this->assertTrue($response[0] == '1' && $response[1] >= 2);
    }

    public function testRoutesCount()
    {
        $count = Injector::resolve(RouteCollection::class)->count();
        $this->assertTrue($count >= '2');
    }

    public function testRoutesMatch()
    {
        $request = new CustomRequest([
            'path' => 'wordpress/app-test',
            'method' => 'GET'
        ]);

        $route = (new Routes($request, [
            'root' => 'https://example.com/wordpress/'
        ], Injector::resolve(RouteCollection::class)))->detectRoute();

        $matched_route = $route->match[0];

        $this->assertTrue($matched_route == 'wordpress/app-test');
    }

    public function testRoutesMatchRoot()
    {
        $request = new CustomRequest([
            'path' => '/app-test/',
            'method' => 'GET'
        ]);

        $route = (new Routes($request, [
            'root' => 'https://example.com/wordpress/'
        ], Injector::resolve(RouteCollection::class) ))->detectRoute();

        $matched_route = $route->match[0];

        $this->assertTrue($matched_route == 'app-test/');
    }

    public function testRouteNamedWithHelpers()
    {
        /** @var RouteCollection $routes */
        tr_route()
            ->get()
            ->match('/about/me/(.+)', ['id'])
            ->name('about.me', '/about/me/:id/:given-name');

        $located = tr_route_lookup('about.me');

        $built = $located->buildUrlFromPattern([
            ':id' => 123,
            'given-name' => 'kevin'
        ], false);

        $built_helper = tr_route_url_lookup('about.me', [
            ':id' => 987,
            'given-name' => 'ben'
        ], false);

        $this->assertTrue($built == '/about/me/123/kevin');
        $this->assertTrue($built_helper == '/about/me/987/ben');

        $form = new Form('post', 'update', 1, WPPost::class);

        $formUrl = $form->useRoute('about.me')->getFormUrl();
        $this->assertContains('/about/me/1/:given-name', $formUrl);
    }

    public function testRouteNamedNoPatternAutoDetect()
    {
        $routes = new ApplicationRoutes();
        $route = new Route();

        $route
            ->get()
            ->post()
            ->match('/user/(.+)/job/([^\/]+)/', ['id', 'job'])
            ->name('testing.no.pattern')
            ->register($routes);

        $located = $routes->getNamedRoute('testing.no.pattern');

        $built = $located->buildUrlFromPattern([
            ':id' => 123,
            ':job' => 987,
        ]);

        $this->assertContains('/user/123/job/987', $built);
    }

    public function testRedirectToRoute()
    {
        $redirect = new Redirect();
        $redirect->toRoute('about.me', [
            'id' => 345,
            'given-name' => 'kevin'
        ]);

        $this->assertContains('/about/me/345/kevin', $redirect->url);
    }

}