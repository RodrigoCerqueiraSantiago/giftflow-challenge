<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GiftFlow - Apresentação</title>
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-10 font-sans" x-data="app()">

    <div class="max-w-3xl mx-auto bg-white shadow-xl overflow-hidden">

        <div class="flex border-b border-gray-200">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-gray-700">🎁 GiftFlow - Demo Técnica</h1>
                <p class="text-gray-600 mt-2">Painel de Teste de API de Resgate</p>
            </div>
            <div>
                <div class="p-6 flex flex-col justify-center h-full">
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Candidato</p>
                    <p class="text-lg font-semibold text-gray-700">Rodrigo Cerqueira Santiago</p>
                    <p class="text-sm text-gray-600">Desenvolvedor Full Stack</p>
                    <p class="text-sm text-gray-600">santiagophp@yahoo.com</p>
                    <p class="text-sm text-gray-600">(21) 98404-0773</p>
                </div>
            </div>
        </div>

        <div class="p-8 space-y-8">
            <!-- Scenarios -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button @click="redeem('GFLOW-TEST-0001')"
                    class="p-4 bg-green-50 border border-green-200 hover:bg-green-100 transition text-left group">
                    <span class="block text-sm font-bold text-green-700">Cenário 1: Sucesso</span>
                    <span class="text-xs text-green-600">Código Disponível</span>
                    <code class="block mt-2 text-xs bg-white p-1 border border-green-100">GFLOW-TEST-0001</code>
                </button>

                <button @click="redeem('GFLOW-USED-0003')"
                    class="p-4 bg-yellow-50 border border-yellow-200 hover:bg-yellow-100 transition text-left">
                    <span class="block text-sm font-bold text-yellow-700">Cenário 2: Erro 409</span>
                    <span class="text-xs text-yellow-600">Já Resgatado</span>
                    <code class="block mt-2 text-xs bg-white p-1 border border-yellow-100">GFLOW-USED-0003</code>
                </button>

                <button @click="redeem('GFLOW-INVALID-XXXX')"
                    class="p-4 bg-red-50 border border-red-200 hover:bg-red-100 transition text-left">
                    <span class="block text-sm font-bold text-red-700">Cenário 3: Erro 404</span>
                    <span class="text-xs text-red-600">Código Inválido</span>
                    <code class="block mt-2 text-xs bg-white p-1 border border-red-100">GFLOW-INV...</code>
                </button>
            </div>

            <!-- Manual Input & Settings -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-xs text-gray-500 font-semibold uppercase">Email do Usuário</label>
                    <input type="email" x-model="email"
                        class="p-2 border border-gray-300 focus:ring focus:ring-indigo-200 outline-none w-full">
                    <span class="text-[10px] text-gray-400">Troque o e-mail para testar conflitos (Erro 409).</span>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-gray-500 font-semibold uppercase">Código Manual</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="customCode" placeholder="GFLOW-..."
                            class="flex-1 p-2 border border-gray-300 focus:ring focus:ring-indigo-200 outline-none">
                        <button @click="redeem(customCode)"
                            class="px-6 py-2 bg-gray-800 text-white hover:bg-gray-700 font-bold">Testar</button>
                    </div>
                </div>
            </div>

            <!-- Console Output -->
            <div class="bg-gray-900 p-4 font-mono text-sm h-64 overflow-y-auto shadow-inner">
                <div class="flex justify-between items-center mb-2 border-b border-gray-700 pb-2">
                    <span class="text-gray-400">Terminal Output</span>
                    <button @click="logs = []" class="text-xs text-gray-500 hover:text-white">Limpar</button>
                </div>
                <template x-for="log in logs" :key="log.id">
                    <div class="mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500" x-text="log.time"></span>
                            <span :class="getStatusColor(log.status)" class="font-bold px-1 text-xs"
                                x-text="log.method + ' ' + log.status"></span>
                            <span class="text-gray-300" x-text="log.url"></span>
                        </div>
                        <pre class="mt-1 ml-12 text-xs text-gray-400 overflow-x-auto" x-text="JSON.stringify(log.data, null, 2)"></pre>
                    </div>
                </template>
                <div x-show="logs.length === 0" class="text-gray-600 italic">Aguardando requisições...</div>
            </div>
        </div>

        <div class="bg-gray-50 px-8 py-4 border-t text-xs text-center text-gray-500">
            Dica: Abra o arquivo <code>storage/app/private/gift_codes.json</code> para ver as mudanças em tempo real.
        </div>
    </div>

    <script>
        function app() {
            return {
                customCode: '',
                email: 'demo@apresentacao.com',
                logs: [],

                async redeem(code) {
                    if (!code) return;

                    const payload = {
                        code: code,
                        user: {
                            email: this.email
                        }
                    };

                    this.addLog('POST', 'Sending...', '/api/redeem', payload);

                    try {
                        const res = await fetch('/api/redeem', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await res.json();
                        this.addLog('POST', res.status, '/api/redeem', data);
                    } catch (error) {
                        this.addLog('ERR', 'Failed', '/api/redeem', {
                            error: error.message
                        });
                    }
                },

                addLog(method, status, url, data) {
                    this.logs.unshift({
                        id: Date.now(),
                        time: new Date().toLocaleTimeString(),
                        method,
                        status,
                        url,
                        data
                    });
                },

                getStatusColor(status) {
                    if (status === 200) return 'bg-green-900 text-green-200';
                    if (status === 409) return 'bg-yellow-900 text-yellow-200';
                    if (status === 404) return 'bg-red-900 text-red-200';
                    if (status === 422) return 'bg-orange-900 text-orange-200';
                    return 'bg-gray-700 text-gray-200';
                }
            }
        }
    </script>
</body>

</html>
