<?php
/**
 * Created by PhpStorm.
 * User: Hamza Alayed
 * Date: 12/29/18
 * Time: 9:53 AM.
 */

namespace Shamaseen\Repository\Generator\Bases;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Class BaseController.
 */
class Controller extends \App\Http\Controllers\Controller
{
    /**
     * @var Contract
     */
    protected $interface;

    protected $limit = 10;

    protected $routeIndex = '';

    protected $pageTitle = '';
    protected $createRoute = '';

    protected $viewIndex = '';
    protected $viewCreate = '';
    protected $viewEdit = '';
    protected $viewShow = '';
    protected $breadcrumbs;

    protected $menu;
    protected $search ;
    protected $selectedMenu = [];
    protected $isAPI = false;
    protected $trash = false;
    protected $params = [];
    /**
     * @var Request
     */
    private $request;

    /**
     * BaseController constructor.
     *
     * @param Contract $interface
     * @param Request $request
     */
    public function __construct(Contract $interface, Request $request)
    {
        $this->menu = new Collection();
        $this->breadcrumbs = new Collection();

        $language = $request->header('Language', 'en');
        if (!in_array($language, \Config::get('app.locales', []))) {
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

        if (in_array($limit, [20, 30, 40, 50])) {
            $this->limit = $limit;
        }

        \App::setLocale($language);
        $this->interface = $interface;
        $this->isAPI = $request->expectsJson();

        if (!$this->isAPI) {
            $this->breadcrumbs = new Collection();
            $this->search = new Collection();
            \View::share('pageTitle', $this->pageTitle . ' | ' . \Config::get('app.name'));
            \View::share('breadcrumbs', $this->breadcrumbs);
            \View::share('menu', $this->menu);
            \View::share('search', $this->search);
            \View::share('selectedMenu', $this->selectedMenu);
        }
        $this->request = $request;
    }

    /**
     * Display a listing of the resource.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = $this->interface->simplePaginate($this->limit, $this->request->all());
        if (!$this->isAPI) {
            \View::share('pageTitle', 'List ' . $this->pageTitle . ' | ' . \Config::get('app.name'));
            $this->breadcrumbs->put('index', [
                'link' => $this->routeIndex,
                'text' => $this->pageTitle,
            ]);

            return view($this->viewIndex, $this->params)
                ->with('entities', $data)
                ->with('createRoute', $this->createRoute)
                ->with('filters', $this->request->all());
        }
        if ($data->hasMorePages()) {
            return response()->json($data, JsonResponse::HTTP_PARTIAL_CONTENT);
        }
        if (0 == $data->count()) {
            return response()->json($data, JsonResponse::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE);
        }

        return response()->json($data, JsonResponse::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!$this->isAPI) {
            \View::share('pageTitle', 'Create ' . $this->pageTitle . ' | ' . \Config::get('app.name'));
            $this->breadcrumbs->put('create', [
                'link' => $this->createRoute,
                'text' => trans('common/others.create'),
            ]);

            return view($this->viewCreate, $this->params);
        }

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function baseStore()
    {
        $entity = $this->interface->create($this->request->except(['_token', '_method']));
        if (!$this->isAPI) {
            if ($entity) {
                return \Redirect::to($this->routeIndex)->with('message', __('messages.success'));
            }

            return \Redirect::to($this->routeIndex)->with('error', __('messages.error'));
        }

        if ($entity) {
            return response()->json(
                ['status' => true, 'message' => __('messages.success'), 'data' => $entity],
                JsonResponse::HTTP_OK
            );
        }

        return response()->json(
            ['status' => false, 'message' => __('messages.error')],
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Display the specified resource.
     *
     * @param int $entityId
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function show($entityId)
    {
        $entity = $this->interface->find($entityId);
        if (!$this->isAPI) {
            if (!$entity) {
                return \Redirect::to($this->routeIndex)->with('warning', __('messages.not_found'));
            }
            \View::share('pageTitle', 'View ' . $this->pageTitle . ' | ' . \Config::get('app.name'));
            $this->breadcrumbs->put('view', [
                'link' => '',
                'text' => $entity->name ?? $entity->title ?? __('messages.view'),
            ]);

            return view($this->viewShow, $this->params)
                ->with('entity', $entity);
        }
        if (!$entity) {
            return response()->json(null, JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json(
            ['status' => true, 'message' => __('messages.success'), 'data' => $entity],
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $entityId
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function edit($entityId)
    {
        $entity = $this->interface->find($entityId);
        if (!$this->isAPI) {
            if (!$entity) {
                return \Redirect::to($this->routeIndex)->with('warning', __('messages.not_found'));
            }
            $this->breadcrumbs->put('edit', [
                'link' => '',
                'text' => $entity->name ?? $entity->title ?? __('messages.view'),
            ]);

            return view($this->viewEdit, $this->params)
                ->with('entity', $entity);
        }

        return response()->json(null, JsonResponse::HTTP_NOT_FOUND);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $entityId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function baseUpdate($entityId)
    {
        $entity = $this->interface->find($entityId);
        $saved = false;

        if ($entity) {
            $saved = $this->interface->update($entityId, $this->request->except(['_token', '_method']));
        }

        if (!$this->isAPI) {
            if (!$entity) {
                return \Redirect::to($this->routeIndex)->with('warning', __('messages.not_found'));
            }

            if ($saved) {
                return \Redirect::to($this->routeIndex)->with('message', __('messages.success'));
            }

            return \Redirect::to($this->routeIndex)->with('error', __('messages.not_modified'));
        }

        if (!$entity) {
            return response()->json(null, JsonResponse::HTTP_NOT_FOUND);
        }

        if ($saved) {
            return response()->json(
                ['status' => true, 'message' => __('messages.success'), 'data' => $entity],
                JsonResponse::HTTP_OK
            );
        }

        return response()->json(null, JsonResponse::HTTP_NOT_MODIFIED);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $entityId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($entityId)
    {
        $entity = $this->interface->find($entityId);
        $deleted = false;

        if ($entity) {
            $deleted = $this->interface->delete($entityId);
        }
        if (!$this->isAPI) {
            if (!$entity) {
                return \Redirect::to($this->routeIndex)->with('warning', __('messages.not_found'));
            }
            if ($deleted) {
                return \Redirect::to($this->routeIndex)->with('message', __('messages.success'));
            }

            return \Redirect::to($this->routeIndex)->with('error', __('messages.not_modified'));
        }

        if ($entity) {
            return response()->json(null, JsonResponse::HTTP_NOT_FOUND);
        }

        if ($deleted) {
            return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
        }

        return response()->json(null, JsonResponse::HTTP_NOT_MODIFIED);
    }

    /**
     * Restore the specified resource from storage.
     *
     * @param int $entityId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore($entityId)
    {
        $entity = $this->interface->restore($entityId);

        if (!$this->isAPI) {
            if (!$entity) {
                return \Redirect::to($this->routeIndex)->with('warning', __('messages.not_found'));
            }
            if ($entity) {
                return \Redirect::to($this->routeIndex)->with('message', __('messages.success'));
            }

            return \Redirect::to($this->routeIndex)->with('error', __('messages.not_modified'));
        }

        if (!$entity) {
            return response()->json(null, JsonResponse::HTTP_NOT_FOUND);
        }

        if ($entity) {
            return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
        }

        return response()->json(null, JsonResponse::HTTP_NOT_MODIFIED);
    }

    /**
     * Restore the specified resource from storage.
     *
     * @param int $entityId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function forceDelete($entityId)
    {
        $entity = $this->interface->forceDelete($entityId);

        if (!$this->isAPI) {
            if (!$entity) {
                return \Redirect::to($this->routeIndex)->with('warning', __('messages.not_found'));
            }
            if ($entity) {
                return \Redirect::to($this->routeIndex)->with('message', __('messages.success'));
            }

            return \Redirect::to($this->routeIndex)->with('error', __('messages.not_modified'));
        }

        if (!$entity) {
            return response()->json(null, JsonResponse::HTTP_NOT_FOUND);
        }

        if ($entity) {
            return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
        }

        return response()->json(null, JsonResponse::HTTP_NOT_MODIFIED);
    }
}
