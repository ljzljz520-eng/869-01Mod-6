<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>第三方脚本风险告警 - 探针管理后台</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
</head>

<body class="bg-gray-100 min-h-screen text-gray-800">
    <div id="app" class="pb-10">
        <header class="bg-white shadow">
            <div
                class="container mx-auto px-4 md:px-6 py-4 flex flex-col md:flex-row justify-between items-center space-y-3 md:space-y-0">
                <div class="flex items-center space-x-2">
                    <i class="ri-shield-warning-line text-red-600 text-2xl"></i>
                    <h1 class="text-xl font-bold font-sans">第三方脚本风险告警</h1>
                </div>
                <div class="text-sm text-gray-500 flex items-center space-x-4">
                    <span>当前时间: {{ currentTime }}</span>
                    <a href="/admin.php" class="text-blue-600 hover:text-blue-800 font-medium">访客管理</a>
                    <a href="/" target="_blank" class="text-blue-600 hover:text-blue-800 font-medium">访问前台</a>
                </div>
            </div>
        </header>

        <main class="container mx-auto px-4 md:px-6 py-6 md:py-8">

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-gray-500 text-xs mb-1">脚本总数</div>
                            <div class="text-2xl font-bold text-gray-800">{{ stats.total }}</div>
                        </div>
                        <i class="ri-code-s-slash-line text-2xl text-gray-400"></i>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-gray-500 text-xs mb-1">待审核</div>
                            <div class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</div>
                        </div>
                        <i class="ri-time-line text-2xl text-yellow-400"></i>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-gray-500 text-xs mb-1">已备案</div>
                            <div class="text-2xl font-bold text-green-600">{{ stats.approved }}</div>
                        </div>
                        <i class="ri-shield-check-line text-2xl text-green-400"></i>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-gray-500 text-xs mb-1">观察中</div>
                            <div class="text-2xl font-bold text-blue-600">{{ stats.observed }}</div>
                        </div>
                        <i class="ri-eye-line text-2xl text-blue-400"></i>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-gray-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-gray-500 text-xs mb-1">已禁用</div>
                            <div class="text-2xl font-bold text-gray-600">{{ stats.disabled }}</div>
                        </div>
                        <i class="ri-forbid-line text-2xl text-gray-400"></i>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-red-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-gray-500 text-xs mb-1">高风险</div>
                            <div class="text-2xl font-bold text-red-600">{{ stats.high_risk }}</div>
                        </div>
                        <i class="ri-alert-line text-2xl text-red-400"></i>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
                <div class="flex flex-wrap gap-3 items-center w-full md:w-auto">
                    <div class="relative flex-1 md:w-64">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="ri-search-line text-gray-400"></i>
                        </span>
                        <input type="text" v-model="searchQuery" @keyup.enter="fetchData(1)"
                            class="w-full py-2 pl-10 pr-4 text-gray-700 bg-white border rounded-lg focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder="搜索脚本名称/域名...">
                    </div>
                    <select v-model="filterStatus" @change="fetchData(1)"
                        class="py-2 px-3 border rounded-lg text-gray-700 bg-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">全部状态</option>
                        <option value="pending">待审核</option>
                        <option value="approved">已备案</option>
                        <option value="observed">观察中</option>
                        <option value="disabled">已禁用</option>
                    </select>
                    <select v-model="filterType" @change="fetchData(1)"
                        class="py-2 px-3 border rounded-lg text-gray-700 bg-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">全部类型</option>
                        <option value="ad_pixel">广告像素</option>
                        <option value="customer_service">客服插件</option>
                        <option value="analytics">统计脚本</option>
                        <option value="other">其他</option>
                    </select>
                </div>
                <button @click="fetchData(1)"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="ri-refresh-line mr-1"></i> 刷新
                </button>
            </div>

            <div v-if="stats.pending > 0"
                class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6 rounded-r-lg flex items-start">
                <i class="ri-alert-warning-line text-yellow-500 text-xl mr-3 mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-yellow-800 font-medium">
                        有 <span class="font-bold">{{ stats.pending }}</span> 个新脚本等待审核
                    </p>
                    <p class="text-yellow-700 text-sm mt-1">
                        未经登记的脚本不会进入正式监测，请及时审核处理。新发现的脚本默认为"待审核"状态。
                    </p>
                </div>
                <button @click="filterStatus = 'pending'; fetchData(1)"
                    class="text-yellow-700 hover:text-yellow-900 text-sm font-medium">
                    立即处理 <i class="ri-arrow-right-line"></i>
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-sm tracking-wider whitespace-nowrap">
                                <th class="px-6 py-4 font-semibold">脚本名称</th>
                                <th class="px-6 py-4 font-semibold">类型</th>
                                <th class="px-6 py-4 font-semibold">状态</th>
                                <th class="px-6 py-4 font-semibold">风险等级</th>
                                <th class="px-6 py-4 font-semibold">检测次数</th>
                                <th class="px-6 py-4 font-semibold">发现时间</th>
                                <th class="px-6 py-4 font-semibold text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="item in scripts" :key="item.id"
                                class="hover:bg-gray-50 transition whitespace-nowrap">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ item.script_name }}</div>
                                    <div class="text-xs text-gray-500 truncate max-w-xs" :title="item.script_url">
                                        {{ item.script_domain }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getTypeClass(item.script_type)"
                                        class="px-2 py-1 rounded text-xs font-medium">
                                        {{ getTypeLabel(item.script_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getStatusClass(item.status)"
                                        class="px-2 py-1 rounded text-xs font-medium">
                                        <i :class="getStatusIcon(item.status)" class="mr-1"></i>
                                        {{ getStatusLabel(item.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getRiskClass(item.risk_level)"
                                        class="px-2 py-1 rounded text-xs font-medium">
                                        {{ getRiskLabel(item.risk_level) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ item.detection_count }} 次
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ item.created_at }}
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button @click="openDetail(item)"
                                        class="text-blue-600 hover:text-blue-800 text-sm">详情</button>
                                    <button @click="openStatusModal(item)"
                                        class="text-gray-600 hover:text-gray-800 text-sm">状态</button>
                                </td>
                            </tr>
                            <tr v-if="scripts.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                    暂无数据
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                    <div class="text-sm text-gray-500">
                        共 {{ total }} 条记录，第 {{ page }} / {{ totalPages }} 页
                    </div>
                    <div class="flex space-x-2">
                        <button @click="prevPage" :disabled="page <= 1"
                            class="px-3 py-1 bg-white border rounded hover:bg-gray-100 disabled:opacity-50">上一页</button>
                        <button @click="nextPage" :disabled="page >= totalPages"
                            class="px-3 py-1 bg-white border rounded hover:bg-gray-100 disabled:opacity-50">下一页</button>
                    </div>
                </div>
            </div>

        </main>

        <div v-if="showDetailModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            @click.self="showDetailModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">
                <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50 flex-shrink-0">
                    <div class="flex items-center space-x-3">
                        <h3 class="text-lg font-bold">脚本详情</h3>
                        <span :class="getStatusClass(currentScript.status)"
                            class="px-2 py-0.5 rounded text-xs font-medium">
                            {{ getStatusLabel(currentScript.status) }}
                        </span>
                    </div>
                    <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600"><i
                            class="ri-close-line text-xl"></i></button>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <div class="p-6">
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500 block mb-1 text-xs">脚本名称</span>
                                    <span class="font-medium text-gray-800">{{ currentScript.script_name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block mb-1 text-xs">域名</span>
                                    <span class="font-mono text-gray-800 break-all">{{ currentScript.script_domain
                                        }}</span>
                                </div>
                                <div class="md:col-span-2">
                                    <span class="text-gray-500 block mb-1 text-xs">脚本 URL</span>
                                    <span class="font-mono text-gray-600 text-xs break-all">{{ currentScript.script_url
                                        }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block mb-1 text-xs">类型</span>
                                    <span :class="getTypeClass(currentScript.script_type)"
                                        class="px-2 py-0.5 rounded text-xs font-medium">
                                        {{ getTypeLabel(currentScript.script_type) }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block mb-1 text-xs">风险等级</span>
                                    <span :class="getRiskClass(currentScript.risk_level)"
                                        class="px-2 py-0.5 rounded text-xs font-medium">
                                        {{ getRiskLabel(currentScript.risk_level) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                                <i class="ri-database-2-line mr-2 text-blue-500"></i>
                                收集的字段
                            </h4>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="field in currentScript.collect_fields" :key="field"
                                    class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
                                    {{ field }}
                                </span>
                                <span v-if="!currentScript.collect_fields || currentScript.collect_fields.length === 0"
                                    class="text-gray-400 text-sm">暂无数据</span>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                                <i class="ri-file-text-line mr-2 text-gray-500"></i>
                                描述说明
                            </h4>
                            <p class="text-gray-600 text-sm">{{ currentScript.description || '暂无描述' }}</p>
                        </div>

                        <div>
                            <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                                <i class="ri-history-line mr-2 text-green-500"></i>
                                最近检测记录 ({{ detections.length }})
                            </h4>
                            <div class="border rounded-lg overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr class="text-gray-600 text-xs">
                                            <th class="px-4 py-2 text-left">访客 IP</th>
                                            <th class="px-4 py-2 text-left">加载耗时</th>
                                            <th class="px-4 py-2 text-left">同意后启动</th>
                                            <th class="px-4 py-2 text-left">检测时间</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="det in detections" :key="det.id" class="hover:bg-gray-50">
                                            <td class="px-4 py-2 text-gray-800">{{ det.ip || '-' }}</td>
                                            <td class="px-4 py-2 text-gray-600">{{ det.load_time }} ms</td>
                                            <td class="px-4 py-2">
                                                <span v-if="det.start_after_consent"
                                                    class="text-green-600 text-xs">是</span>
                                                <span v-else class="text-red-600 text-xs">否</span>
                                            </td>
                                            <td class="px-4 py-2 text-gray-500 text-xs">{{ det.detected_at }}</td>
                                        </tr>
                                        <tr v-if="detections.length === 0">
                                            <td colspan="4" class="px-4 py-6 text-center text-gray-400">暂无检测记录
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-between flex-shrink-0">
                    <button @click="openStatusModal(currentScript)"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        <i class="ri-settings-3-line mr-1"></i> 修改状态
                    </button>
                    <button @click="showDetailModal = false"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">关闭</button>
                </div>
            </div>
        </div>

        <div v-if="showStatusModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            @click.self="showStatusModal = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-bold">修改脚本状态</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ statusForm.script_name }}</p>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">状态设置</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button @click="statusForm.status = 'pending'" :class="[
                                'p-3 rounded-lg border-2 text-left transition',
                                statusForm.status === 'pending' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200 hover:border-gray-300'
                            ]">
                                <div class="flex items-center">
                                    <i class="ri-time-line text-yellow-500 mr-2"></i>
                                    <span class="font-medium text-sm">待审核</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">新发现的脚本</p>
                            </button>
                            <button @click="statusForm.status = 'approved'" :class="[
                                'p-3 rounded-lg border-2 text-left transition',
                                statusForm.status === 'approved' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'
                            ]">
                                <div class="flex items-center">
                                    <i class="ri-shield-check-line text-green-500 mr-2"></i>
                                    <span class="font-medium text-sm">已备案</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">安全可信任</p>
                            </button>
                            <button @click="statusForm.status = 'observed'" :class="[
                                'p-3 rounded-lg border-2 text-left transition',
                                statusForm.status === 'observed' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'
                            ]">
                                <div class="flex items-center">
                                    <i class="ri-eye-line text-blue-500 mr-2"></i>
                                    <span class="font-medium text-sm">观察中</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">持续监控</p>
                            </button>
                            <button @click="statusForm.status = 'disabled'" :class="[
                                'p-3 rounded-lg border-2 text-left transition',
                                statusForm.status === 'disabled' ? 'border-gray-500 bg-gray-100' : 'border-gray-200 hover:border-gray-300'
                            ]">
                                <div class="flex items-center">
                                    <i class="ri-forbid-line text-gray-500 mr-2"></i>
                                    <span class="font-medium text-sm">已禁用</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">阻止运行</p>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">备注说明</label>
                        <textarea v-model="statusForm.description" rows="3"
                            class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="可选：添加状态变更的原因说明..."></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-end space-x-3">
                    <button @click="showStatusModal = false"
                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">取消</button>
                    <button @click="saveStatus"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">保存</button>
                </div>
            </div>
        </div>

    </div>

    <script>
        const { createApp, ref, onMounted } = Vue;

        createApp({
            setup() {
                const scripts = ref([]);
                const total = ref(0);
                const page = ref(1);
                const totalPages = ref(1);
                const searchQuery = ref('');
                const filterStatus = ref('');
                const filterType = ref('');
                const stats = ref({
                    total: 0, pending: 0, approved: 0,
                    observed: 0, disabled: 0, high_risk: 0, today_detections: 0
                });

                const showDetailModal = ref(false);
                const showStatusModal = ref(false);
                const currentScript = ref({});
                const detections = ref([]);
                const statusForm = ref({
                    id: null, script_name: '', status: 'pending', description: ''
                });
                const currentTime = ref('');

                const typeLabels = {
                    'ad_pixel': '广告像素',
                    'customer_service': '客服插件',
                    'analytics': '统计脚本',
                    'other': '其他'
                };

                const statusLabels = {
                    'pending': '待审核',
                    'approved': '已备案',
                    'observed': '观察中',
                    'disabled': '已禁用'
                };

                const riskLabels = {
                    'low': '低风险',
                    'medium': '中风险',
                    'high': '高风险',
                    'critical': '严重'
                };

                const getTypeLabel = (type) => typeLabels[type] || type;
                const getStatusLabel = (status) => statusLabels[status] || status;
                const getRiskLabel = (risk) => riskLabels[risk] || risk;

                const getTypeClass = (type) => {
                    const map = {
                        'ad_pixel': 'bg-purple-100 text-purple-700',
                        'customer_service': 'bg-teal-100 text-teal-700',
                        'analytics': 'bg-indigo-100 text-indigo-700',
                        'other': 'bg-gray-100 text-gray-700'
                    };
                    return map[type] || map['other'];
                };

                const getStatusClass = (status) => {
                    const map = {
                        'pending': 'bg-yellow-100 text-yellow-700',
                        'approved': 'bg-green-100 text-green-700',
                        'observed': 'bg-blue-100 text-blue-700',
                        'disabled': 'bg-gray-200 text-gray-600'
                    };
                    return map[status] || map['pending'];
                };

                const getStatusIcon = (status) => {
                    const map = {
                        'pending': 'ri-time-line',
                        'approved': 'ri-shield-check-line',
                        'observed': 'ri-eye-line',
                        'disabled': 'ri-forbid-line'
                    };
                    return map[status] || 'ri-time-line';
                };

                const getRiskClass = (risk) => {
                    const map = {
                        'low': 'bg-green-100 text-green-700',
                        'medium': 'bg-yellow-100 text-yellow-700',
                        'high': 'bg-orange-100 text-orange-700',
                        'critical': 'bg-red-100 text-red-700'
                    };
                    return map[risk] || map['medium'];
                };

                const fetchStats = async () => {
                    try {
                        const res = await fetch('/api.php?action=script_stats');
                        const json = await res.json();
                        if (json.status === 'success') {
                            stats.value = json;
                        }
                    } catch (e) {
                        console.error(e);
                    }
                };

                const fetchData = async (p = 1) => {
                    try {
                        let url = `/api.php?action=script_list&page=${p}`;
                        if (searchQuery.value) url += `&search=${encodeURIComponent(searchQuery.value)}`;
                        if (filterStatus.value) url += `&status=${filterStatus.value}`;
                        if (filterType.value) url += `&type=${filterType.value}`;

                        const res = await fetch(url);
                        const json = await res.json();
                        if (json.status === 'success') {
                            scripts.value = json.data;
                            total.value = json.total;
                            page.value = json.page;
                            totalPages.value = json.pages;
                        }
                    } catch (e) {
                        console.error(e);
                    }
                    fetchStats();
                };

                const prevPage = () => {
                    if (page.value > 1) fetchData(page.value - 1);
                };

                const nextPage = () => {
                    if (page.value < totalPages.value) fetchData(page.value + 1);
                };

                const openDetail = async (item) => {
                    currentScript.value = item;
                    showDetailModal.value = true;
                    detections.value = [];

                    try {
                        const res = await fetch(`/api.php?action=script_detail&id=${item.id}`);
                        const json = await res.json();
                        if (json.status === 'success') {
                            currentScript.value = json.script;
                            detections.value = json.detections;
                        }
                    } catch (e) {
                        console.error(e);
                    }
                };

                const openStatusModal = (item) => {
                    statusForm.value = {
                        id: item.id,
                        script_name: item.script_name,
                        status: item.status,
                        description: item.description || ''
                    };
                    showDetailModal.value = false;
                    showStatusModal.value = true;
                };

                const saveStatus = async () => {
                    try {
                        const res = await fetch('/api.php?action=script_status', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                id: statusForm.value.id,
                                status: statusForm.value.status,
                                description: statusForm.value.description
                            })
                        });
                        const json = await res.json();
                        if (json.status === 'success') {
                            showStatusModal.value = false;
                            fetchData(page.value);
                        } else {
                            alert('保存失败: ' + (json.message || '未知错误'));
                        }
                    } catch (e) {
                        alert('错误: ' + e.message);
                    }
                };

                setInterval(() => {
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    const seconds = String(now.getSeconds()).padStart(2, '0');
                    currentTime.value = `${hours}:${minutes}:${seconds}`;
                }, 1000);

                onMounted(() => {
                    fetchData();
                    fetchStats();
                });

                return {
                    scripts, total, page, totalPages, searchQuery, filterStatus, filterType, stats,
                    showDetailModal, showStatusModal, currentScript, detections, statusForm, currentTime,
                    getTypeLabel, getStatusLabel, getRiskLabel,
                    getTypeClass, getStatusClass, getStatusIcon, getRiskClass,
                    fetchData, prevPage, nextPage, openDetail, openStatusModal, saveStatus
                };
            }
        }).mount('#app');
    </script>
</body>

</html>
