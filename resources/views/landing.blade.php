@extends('layouts.landing')

@section('content')
    <!-- Header -->
    <header class="bg-white shadow-sm fixed w-full top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <img src="{{ asset('images/logo.svg') }}" alt="ODSGeo" class="h-10">
                <span class="ml-2 text-2xl font-bold text-blue-900">ODSGeo</span>
            </div>
            <a href="{{ route('login') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                Já é cliente? Faça login
            </a>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="gradient-bg pt-32 pb-20 text-white">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">
                A maneira mais inteligente de gerar sua planilha ODS para o INCRA!
            </h1>
            <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
                Importe coordenadas, organize projetos e exporte seu arquivo ODS com apenas alguns cliques. Tudo online, rápido e descomplicado.
            </p>
            <a href="{{ route('register') }}" class="bg-white text-blue-600 px-8 py-4 rounded-lg text-xl font-semibold hover:bg-gray-100 transition duration-300 inline-block">
                Teste gratuito por 7 dias
            </a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Funcionalidades que transformam seu trabalho</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card bg-gray-50 p-6 rounded-xl shadow-sm">
                    <div class="text-blue-600 text-3xl mb-4">
                        <i class="fas fa-file-import"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Importação Flexível</h3>
                    <p class="text-gray-600">Importe coordenadas via Excel, TXT e memorial descritivo INCRA com facilidade.</p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card bg-gray-50 p-6 rounded-xl shadow-sm">
                    <div class="text-blue-600 text-3xl mb-4">
                        <i class="fas fa-file-export"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Exportação Automática</h3>
                    <p class="text-gray-600">Exportação automática da planilha ODS compatível com o SIGEF.</p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card bg-gray-50 p-6 rounded-xl shadow-sm">
                    <div class="text-blue-600 text-3xl mb-4">
                        <i class="fas fa-folder-tree"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Organização Inteligente</h3>
                    <p class="text-gray-600">Organize por Cliente, Projeto e Serviço de forma intuitiva.</p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card bg-gray-50 p-6 rounded-xl shadow-sm">
                    <div class="text-blue-600 text-3xl mb-4">
                        <i class="fas fa-upload"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Upload de Documentos</h3>
                    <p class="text-gray-600">Upload de arquivos e documentos para cada projeto.</p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card bg-gray-50 p-6 rounded-xl shadow-sm">
                    <div class="text-blue-600 text-3xl mb-4">
                        <i class="fas fa-code-branch"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Controle de Versões</h3>
                    <p class="text-gray-600">Interface amigável com controle de versões integrado.</p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card bg-gray-50 p-6 rounded-xl shadow-sm">
                    <div class="text-blue-600 text-3xl mb-4">
                        <i class="fas fa-table"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Geração de Tabelas</h3>
                    <p class="text-gray-600">Geração automática de tabela de vértices e dados técnicos.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonial Section -->
    <section class="py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto text-center">
                <div class="text-blue-600 text-5xl mb-6">
                    <i class="fas fa-quote-left"></i>
                </div>
                <p class="text-xl text-gray-700 mb-8">
                    "Gerei a planilha ODS de um projeto de 28 vértices em menos de 5 minutos. Economizei horas de trabalho!"
                </p>
                <div class="flex items-center justify-center">
                    <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="ml-4 text-left">
                        <h4 class="font-semibold">João Silva</h4>
                        <p class="text-gray-600">Engenheiro Agrimensor</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Perguntas Frequentes</h2>
            <div class="max-w-3xl mx-auto">
                <div class="mb-8">
                    <h3 class="text-xl font-semibold mb-3">Preciso instalar algo?</h3>
                    <p class="text-gray-600">Não! O ODSGeo é 100% online. Basta acessar pelo navegador e começar a usar.</p>
                </div>
                <div class="mb-8">
                    <h3 class="text-xl font-semibold mb-3">É compatível com meu arquivo de memorial?</h3>
                    <p class="text-gray-600">Sim! Nosso sistema é compatível com os formatos mais comuns de memorial descritivo do INCRA.</p>
                </div>
                <div class="mb-8">
                    <h3 class="text-xl font-semibold mb-3">A exportação realmente segue o modelo do INCRA?</h3>
                    <p class="text-gray-600">Absolutamente! Nossa exportação é 100% compatível com o modelo oficial do INCRA.</p>
                </div>
                <div class="mb-8">
                    <h3 class="text-xl font-semibold mb-3">E se eu tiver dúvidas?</h3>
                    <p class="text-gray-600">Oferecemos suporte técnico completo por email e chat. Nossa equipe está sempre pronta para ajudar!</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 gradient-bg text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-6">Pronto para transformar seu trabalho?</h2>
            <p class="text-xl mb-8">Comece agora mesmo com 7 dias de teste gratuito!</p>
            <a href="{{ route('register') }}" class="bg-white text-blue-600 px-8 py-4 rounded-lg text-xl font-semibold hover:bg-gray-100 transition duration-300 inline-block">
                Teste gratuito por 7 dias
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">ODSGeo</h3>
                    <p class="text-gray-400">A solução mais inteligente para suas planilhas ODS.</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Links Úteis</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">Suporte</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Termos de Uso</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Política de Privacidade</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Contato</h4>
                    <ul class="space-y-2">
                        <li class="text-gray-400">suporte@odsgeo.com.br</li>
                        <li class="text-gray-400">(XX) XXXX-XXXX</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Redes Sociais</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} ODSGeo. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
@endsection 