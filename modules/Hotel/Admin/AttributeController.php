<?php
namespace Modules\Hotel\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\AdminController;
use Modules\Core\Models\Attributes;
use Modules\Core\Models\AttributesTranslation;
use Modules\Core\Models\Terms;
use Modules\Core\Models\TermsTranslation;
use Illuminate\Support\Facades\DB;
use Modules\Hotel\Models\Hotel;

class AttributeController extends AdminController
{
    protected $attributesClass;
    protected $termsClass;
    public function __construct()
    {
        $this->setActiveMenu('admin/module/property-hotels');
        parent::__construct();
        $this->attributesClass = Attributes::class;
        $this->termsClass = Terms::class;
    }
    public function callAction($method, $parameters)
    {
        if(!Hotel::isEnable())
        {
            return redirect('/');
        }
        return parent::callAction($method, $parameters); // TODO: Change the autogenerated stub
    }

    public function index(Request $request)
    {
        $this->checkPermission('hotel_manage_attributes');
        $listAttr = $this->attributesClass::where("service", 'hotel');
        if (!empty($search = $request->query('s'))) {
            $listAttr->where('name', 'LIKE', '%' . $search . '%');
        }
        $listAttr->orderBy('created_at', 'desc');
        $data = [
            'rows'        => $listAttr->get(),
            'row'         => new $this->attributesClass(),
            'translation'    => new AttributesTranslation(),
            'breadcrumbs' => [
                [
                    'name' => __('Property Hotels'),
                    'url'  => 'admin/module/property-hotels'
                ],
                [
                    'name'  => __('Attributes'),
                    'class' => 'active'
                ],
            ]
        ];
        return view('Hotel::admin.attribute.index', $data);
    }

    public function edit(Request $request, $id)
    {
        $row = $this->attributesClass::find($id);
        if (empty($row)) {
            return redirect()->back()->with('error', __('Attributes not found!'));
        }
        $translation = $row->translateOrOrigin($request->query('lang'));
        $this->checkPermission('hotel_manage_attributes');
        $data = [
            'translation'    => $translation,
            'enable_multi_lang'=>true,
            'rows'        => $this->attributesClass::where("service", 'hotel')->get(),
            'row'         => $row,
            'breadcrumbs' => [
                [
                    'name' => __('Hotel'),
                    'url'  => 'admin/module/property-hotels'
                ],
                [
                    'name' => __('Attributes'),
                    'url'  => 'admin/module/property-hotels/attribute'
                ],
                [
                    'name'  => __('Attribute: :name', ['name' => $row->name]),
                    'class' => 'active'
                ],
            ]
        ];
        return view('Hotel::admin.attribute.detail', $data);
    }

    public function store(Request $request)
    {
        $this->checkPermission('hotel_manage_attributes');
        $this->validate($request, [
            'name' => 'required'
        ]);
        $id = $request->input('id');
        if ($id) {
            $row = $this->attributesClass::find($id);
            if (empty($row)) {
                return redirect()->back()->with('error', __('Attributes not found!'));
            }
        } else {
            $row = new $this->attributesClass($request->input());
            $row->service = 'hotel';
        }
        $row->fill($request->input());
        $res = $row->saveOriginOrTranslation($request->input('lang'));
        if ($res) {
            return redirect()->back()->with('success', __('Attribute saved'));
        }
    }

    public function editAttrBulk(Request $request)
    {
        $this->checkPermission('hotel_manage_attributes');
        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids) or !is_array($ids)) {
            return redirect()->back()->with('error', __('Select at least 1 item!'));
        }
        if (empty($action)) {
            return redirect()->back()->with('error', __('Select an Action!'));
        }
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = $this->attributesClass::where("id", $id);
                $query->first();
                if(!empty($query)){
                    $query->delete();
                }
            }
        }
        return redirect()->back()->with('success', __('Updated success!'));
    }

    public function terms(Request $request, $attr_id)
    {
        $this->checkPermission('hotel_manage_attributes');
        $row = $this->attributesClass::find($attr_id);
        if (empty($row)) {
            return redirect()->back()->with('error', __('Term not found'));
        }
        $listTerms = $this->termsClass::where("attr_id", $attr_id);
        if (!empty($search = $request->query('s'))) {
            $listTerms->where('name', 'LIKE', '%' . $search . '%');
        }
        $listTerms->orderBy('created_at', 'desc');
        $data = [
            'rows'        => $listTerms->paginate(20),
            'attr'        => $row,
            "row"         => new $this->termsClass(),
            'translation'    => new TermsTranslation(),
            'breadcrumbs' => [
                [
                    'name' => __('Hotel'),
                    'url'  => 'admin/module/property-hotels'
                ],
                [
                    'name' => __('Attributes'),
                    'url'  => 'admin/module/property-hotels/attribute'
                ],
                [
                    'name'  => __('Attribute: :name', ['name' => $row->name]),
                    'class' => 'active'
                ],
            ]
        ];
        return view('Hotel::admin.terms.index', $data);
    }

    public function term_edit(Request $request, $id)
    {
        $this->checkPermission('hotel_manage_attributes');
        $row = $this->termsClass::find($id);
        if (empty($row)) {
            return redirect()->back()->with('error', __('Term not found'));
        }
        $translation = $row->translateOrOrigin($request->query('lang'));
        $attr = $this->attributesClass::find($row->attr_id);
        $data = [
            'row'         => $row,
            'translation'    => $translation,
            'enable_multi_lang'=>true,
            'breadcrumbs' => [
                [
                    'name' => __('Hotel'),
                    'url'  => 'admin/module/property-hotels'
                ],
                [
                    'name' => __('Attributes'),
                    'url'  => 'admin/module/property-hotels/attribute'
                ],
                [
                    'name' => $attr->name,
                    'url'  => 'admin/module/property-hotels/attribute/terms/' . $row->attr_id
                ],
                [
                    'name'  => __('Term: :name', ['name' => $row->name]),
                    'class' => 'active'
                ],
            ]
        ];
        return view('Hotel::admin.terms.detail', $data);
    }

    public function term_store(Request $request)
    {
        $this->checkPermission('hotel_manage_attributes');
        $this->validate($request, [
            'name' => 'required'
        ]);
        $id = $request->input('id');
        if ($id) {
            $row = $this->termsClass::find($id);
            if (empty($row)) {
                return redirect()->back()->with('error', __('Term not found'));
            }
        } else {
            $row = new $this->termsClass($request->input());
            $row->attr_id = $request->input('attr_id');
        }
        $row->fill($request->input());
        $row->image_id = $request->input('image_id');
        $row->icon = $request->input('icon');
        $res = $row->saveOriginOrTranslation($request->input('lang'));
        if ($res) {
            return redirect()->back()->with('success', __('Term saved'));
        }
    }

    public function editTermBulk(Request $request)
    {
        $this->checkPermission('hotel_manage_attributes');
        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids) or !is_array($ids)) {
            return redirect()->back()->with('error', __('Select at least 1 item!'));
        }
        if (empty($action)) {
            return redirect()->back()->with('error', __('Select an Action!'));
        }
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = $this->termsClass::where("id", $id);
                $query->first();
                if(!empty($query)){
                    $query->delete();
                }
            }
        }
        return redirect()->back()->with('success', __('Updated success!'));
    }

    public function getForSelect2(Request $request)
    {
        $pre_selected = $request->query('pre_selected');
        $selected = $request->query('selected');

        if($pre_selected && $selected){
            if(is_array($selected))
            {
                $query = $this->termsClass::getForSelect2Query('hotel');
                $items = $query->whereIn('bc_terms.id',$selected)->take(50)->get();
                return response()->json([
                    'items'=>$items
                ]);
            }

            if(empty($item)){
                return response()->json([
                    'text'=>''
                ]);
            }else{
                return response()->json([
                    'text'=>$item->name
                ]);
            }
        }
        $q = $request->query('q');
        $query = $this->termsClass::getForSelect2Query('hotel',$q);
        $res = $query->orderBy('bc_terms.id', 'desc')->limit(20)->get();
        return response()->json([
            'results' => $res
        ]);
    }

    public function getAttributeForSelect2(Request $request)
    {
        $q = $request->query('q');
        $query = $this->attributesClass::selectRaw("id,name as text")->where('service','hotel');
        if ($q) {
            $query->where('name', 'like', '%' . $q . '%');
        }
        $res = $query->orderBy('id', 'desc')->limit(20)->get();
        return response()->json([
            'results' => $res
        ]);
    }
}
