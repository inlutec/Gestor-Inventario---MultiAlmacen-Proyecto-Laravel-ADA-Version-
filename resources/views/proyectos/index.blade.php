@extends('proyectos.layout')

@section('title', 'Proyectos')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Proyectos</h1>
        <a href="{{ route('proyectos.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-junta-green-600 hover:bg-junta-green-700">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Proyecto
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white shadow rounded-lg p-4">
        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Buscar</label>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o código..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-junta-green-500 focus:ring-junta-green-500 sm:text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Estado</label>
                <select name="estado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-junta-green-500 focus:ring-junta-green-500 sm:text-sm">
                    <option value="">Todos</option>
                    <option value="planificacion" {{ request('estado') === 'planificacion' ? 'selected' : '' }}>Planificación</option>
                    <option value="en_progreso" {{ request('estado') === 'en_progreso' ? 'selected' : '' }}>En Progreso</option>
                    <option value="pausado" {{ request('estado') === 'pausado' ? 'selected' : '' }}>Pausado</option>
                    <option value="completado" {{ request('estado') === 'completado' ? 'selected' : '' }}>Completado</option>
                    <option value="cancelado" {{ request('estado') === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Prioridad</label>
                <select name="prioridad" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-junta-green-500 focus:ring-junta-green-500 sm:text-sm">
                    <option value="">Todas</option>
                    <option value="baja" {{ request('prioridad') === 'baja' ? 'selected' : '' }}>Baja</option>
                    <option value="media" {{ request('prioridad') === 'media' ? 'selected' : '' }}>Media</option>
                    <option value="alta" {{ request('prioridad') === 'alta' ? 'selected' : '' }}>Alta</option>
                    <option value="critica" {{ request('prioridad') === 'critica' ? 'selected' : '' }}>Crítica</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-junta-green-600 hover:bg-junta-green-700">
                    Filtrar
                </button>
                <a href="{{ route('proyectos.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Lista de Proyectos -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @forelse($proyectos as $proyecto)
            <li>
                <a href="{{ route('proyectos.show', $proyecto) }}" class="block hover:bg-gray-50">
                    <div class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center min-w-0 flex-1">
                                <div class="w-2 h-12 rounded" style="background-color: {{ $proyecto->color }}"></div>
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-base font-medium text-gray-900 truncate">
                                            {{ $proyecto->nombre }}
                                        </p>
                                        <div class="ml-2 flex-shrink-0 flex space-x-2">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $proyecto->prioridad_badge['color'] }}">
                                                {{ $proyecto->prioridad_badge['icono'] }} {{ $proyecto->prioridad_badge['texto'] }}
                                            </span>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $proyecto->estado_badge['color'] }}">
                                                {{ $proyecto->estado_badge['texto'] }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-2 sm:flex sm:justify-between">
                                        <div class="sm:flex">
                                            <p class="flex items-center text-sm text-gray-500">
                                                <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                                </svg>
                                                {{ $proyecto->codigo }}
                                            </p>
                                            @if($proyecto->responsable)
                                            <p class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 sm:ml-6">
                                                <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                                {{ $proyecto->responsable->nombre }}
                                            </p>
                                            @endif
                                        </div>
                                        <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                            @if($proyecto->fecha_fin_estimada)
                                            <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <p>
                                                Vence: <time>{{ $proyecto->fecha_fin_estimada->format('d/m/Y') }}</time>
                                                @if($proyecto->esRetrasado())
                                                <span class="ml-1 text-red-600 font-semibold">⚠ Retrasado</span>
                                                @endif
                                            </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="flex items-center">
                                            <div class="flex-1">
                                                <div class="bg-gray-200 rounded-full h-2">
                                                    <div class="bg-junta-green-600 h-2 rounded-full" style="width: {{ $proyecto->progreso }}%"></div>
                                                </div>
                                            </div>
                                            <span class="ml-2 text-sm text-gray-600">{{ round($proyecto->progreso) }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </li>
            @empty
            <li class="px-4 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No se encontraron proyectos</h3>
                <p class="mt-1 text-sm text-gray-500">Comienza creando un nuevo proyecto.</p>
            </li>
            @endforelse
        </ul>
    </div>

    <!-- Paginación -->
    @if($proyectos->hasPages())
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        {{ $proyectos->links() }}
    </div>
    @endif
</div>
@endsection
