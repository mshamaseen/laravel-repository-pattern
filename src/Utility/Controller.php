<?php
/**
 * Created by PhpStorm.
 * User: Hamza Alayed
 * Date: 12/29/18
 * Time: 9:53 AM.
 */

namespace Shamaseen\Repository\Generator\Utility;

use App;
use Config;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Redirect;
use View;

/**
 * Class BaseController.
 */
class Controller extends App\Http\Controllers\Controller
{
    /**
     * @var ContractInterface
     */
    protected $interface;

    protected $limit = 10;
    protected $maxLimit = 100;

    protected $routeIndex = '';

    protected $pageTitle = '';
    protected $createRoute = '';

    protected $viewIndex = '';
    protected $viewCreate = '';
    protected $viewEdit = '';
    protected $viewShow = '';
    protected $breadcrumbs;

    protected $menu;
    protected $search;
    protected $selectedMenu = [];
    protected $isAPI = false;
    protected $trash = false;
    protected $params = [];
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var JsonResource
     */
    private $resource;

    /**
     * BaseController constructor.
     *
     * @param ContractInterface $interface
     * @param Request $request
     * @param JsonResource $resource
     */
    public function __construct(ContractInterface $interface, Request $request, JsonResource $resource = null)
    {
        $this->menu = new Collection();
        $this->breadcrumbs = new Collection();

        $language = $request->header('Language', 'en');
        if (!in_array($language, Config::get('app.locales', []))) {
            $language = 'en';
        }
        $limit = $request->get('limit', 10);

        if ((bool)$request->get('with-trash', false)) {
            $interface->withTrash();
        }
        if ((bool)$request->get('only-trash', false)) {
            $interface->trash();
        }

        $request->offsetUnset('only-trash');
        $request->offsetUnset('with-trash');
        $request->offsetUnset('limit');

        if ($limit <= $this->maxLimit) {
            $this->limit = $limit;
        }

        App::setLocale($language);
        switch ($language) {
            case 'ar':
                $dir = 'rtl';
                $align = 'right';
                $dirInverse = 'ltr';
                $alignInverse = 'left';
                break;

            case 'en':
            default:
                $dir = 'ltr';
                $align = 'left';
                $dirInverse = 'rtl';
                $alignInverse = 'right';
                break;
        }

        View::share('dir', $dir);
        View::share('align', $align);
        View::share('alignInverse', $alignInverse);
        View::share('dirInverse', $dirInverse);

        $this->interface = $interface;
        $this->isAPI = $request->expectsJson();

        if (!$this->isAPI) {
            $this->breadcrumbs = new Collection();
            $this->search = new Collection();
            View::share('pageTitle', $this->pageTitle . ' | ' . Config::get('app.name'));
            View::share('breadcrumbs', $this->breadcrumbs);
            View::share('menu', $this->menu);
            View::share('search', $this->search);
            View::share('selectedMenu', $this->selectedMenu);
        }
        $this->resource = $resource;
        if (is_null($resource)) {
            $this->resource = new JsonResource([]);
        }

        $this->request = $request;
    }

    /**
     * Display a listing of the resource.
     *
     *
     * @return Factory|JsonResponse|\Illuminate\View\View
     */
    public function index()
    {
        $data = $this->interface->simplePaginate($this->limit, $this->request->all());

        if (!$this->isAPI) {
            View::share('pageTitle', 'List ' . $this->pageTitle . ' | ' . Config::get('app.name'));
            $this->breadcrumbs->put('index', [
                'link' => $this->routeIndex,
                'text' => $this->pageTitle,
            ]);

            return view($this->viewIndex, $this->params)
                ->with('entities', $data)
                ->with('createRoute', $this->createRoute)
                ->with('filters', $this->request->all());
        }

        $resource = $this->resource::collection($data);
        if ($data->hasMorePages()) {
            $custom = collect(['code' => JsonResponse::HTTP_PARTIAL_CONTENT, 'message' => __('repository-generator.partial_content')]);
            $resource = $custom->merge(['data' => $resource]);
            return response()->json($resource, JsonResponse::HTTP_PARTIAL_CONTENT);
        }

        if ($data->isEmpty()) {
            $custom = collect(['code' => JsonResponse::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, 'message' => __('repository-generator.no_content')]);
            $resource = $custom->merge(['data' => $resource]);
            return response()->json($resource, JsonResponse::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE);
        }

        $custom = collect(['code' => JsonResponse::HTTP_OK, 'message' => __('repository-generator.success')]);
        $resource = $custom->merge(['data' => $resource]);
        return response()->json($resource, JsonResponse::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|JsonResponse|\Illuminate\View\View
     */
    public function create()
    {
        if (!$this->isAPI) {
            View::share('pageTitle', 'Create ' . $this->pageTitle . ' | ' . Config::get('app.name'));
            $this->breadcrumbs->put('create', [
                'link' => $this->createRoute,
                'text' => trans('repository-generator.create'),
            ]);

            return view($this->viewCreate, $this->params);
        }

        return response()->json(
            [
                'status' => true,
                'message' => __('repository-generator.no_content'),
                'data' => []
            ],
            JsonResponse::HTTP_NO_CONTENT
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse|mixed
     */
    public function store()
    {
        $entity = $this->interface->create($this->request->except(['_token', '_method']));

        return $this->makeResponse($entity, true);
    }

    /**
     * Display the specified resource.
     *
     * @param int $entityId
     *
     * @return Factory|JsonResponse|RedirectResponse|\Illuminate\View\View
     */
    public function show($entityId)
    {
        $entity = $this->interface->find($entityId);
        if (!$this->isAPI) {
            if (!$entity) {
                return Redirect::to($this->routeIndex)->with('warning', __('repository-generator.not_found'));
            }
            View::share('pageTitle', 'View ' . $this->pageTitle . ' | ' . Config::get('app.name'));
            $this->breadcrumbs->put('view', [
                'link' => '',
                'text' => __('repository-generator.show'),
            ]);

            return view($this->viewShow, $this->params)
                ->with('entity', $entity);
        }

        if (!$entity) {
            return response()->json(
                [
                    'status' => false,
                    'message' => __('repository-generator.not_found'),
                    'data' => []
                ],
                JsonResponse::HTTP_NOT_FOUND
            );
        }
        $resource = new $this->resource($entity);
        return response()->json(
            [
                'status' => true,
                'message' => __('repository-generator.success'),
                'data' => $resource
            ],
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $entityId
     *
     * @return Factory|JsonResponse|RedirectResponse|\Illuminate\View\View
     */
    public function edit($entityId)
    {
        $entity = $this->interface->find($entityId);
        if (!$this->isAPI) {
            if (!$entity) {
                return Redirect::to($this->routeIndex)->with('warning', __('repository-generator.not_found'));
            }
            $this->breadcrumbs->put('edit', [
                'link' => '',
                'text' => __('repository-generator.edit'),
            ]);

            return view($this->viewEdit, $this->params)
                ->with('entity', $entity);
        }

        return response()->json(
            [
                'status' => false,
                'message' => __('repository-generator.not_found'),
                'data' => []
            ],
            JsonResponse::HTTP_NOT_FOUND
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $entityId
     *
     * @return RedirectResponse
     */
    public function update($entityId)
    {
        $entity = $this->interface->update($entityId, $this->request->except(['_token', '_method']));

        return $this->makeResponse($entity, true);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $entityId
     *
     * @return RedirectResponse
     * @throws Exception
     *
     */
    public function destroy($entityId)
    {
        $deleted = $this->interface->delete($entityId);

        return $this->makeResponse($deleted);
    }

    /**
     * Restore the specified resource from storage.
     *
     * @param int $entityId
     *
     * @return RedirectResponse
     */
    public function restore($entityId)
    {
        $entity = $this->interface->restore($entityId);

        return $this->makeResponse($entity);
    }

    /**
     * Restore the specified resource from storage.
     *
     * @param int $entityId
     *
     * @return RedirectResponse
     */
    public function forceDelete($entityId)
    {
        $entity = $this->interface->forceDelete($entityId);

        return $this->makeResponse($entity);
    }

    /**
     * Make response for web or json.
     *
     * @param mixed $entity
     * @param bool $appendEntity
     *
     * @return JsonResponse|RedirectResponse
     */
    public function makeResponse($entity, $appendEntity = false)
    {
        if (!$this->isAPI) {
            if ($entity) {
                return Redirect::to($this->routeIndex)->with('message', __('repository-generator.success'));
            }

            if (null === $entity) {
                return Redirect::to($this->routeIndex)->with('warning', __('repository-generator.not_found'));
            }

            return Redirect::to($this->routeIndex)->with('error', __('repository-generator.not_modified'));
        }

        if ($entity) {
            if ($appendEntity) {
                return response()->json(
                    [
                        'status' => true,
                        'message' => __('repository-generator.success'),
                        'data' => new JsonResource($entity)
                    ],
                    JsonResponse::HTTP_OK
                );
            }

            return response()->json(
                [
                    'status' => false,
                    'message' => __('repository-generator.no_content'),
                    'data' => []
                ],
                JsonResponse::HTTP_NO_CONTENT
            );
        }

        if (null === $entity) {
            return response()->json(
                [
                    'status' => false,
                    'message' => __('repository-generator.not_found'),
                    'data' => []
                ],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        return response()->json(
            [
                'status' => false,
                'message' => __('repository-generator.not_modified'),
                'data' => []
            ],
            JsonResponse::HTTP_NOT_MODIFIED
        );
    }
}
