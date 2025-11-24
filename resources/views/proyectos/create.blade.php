@extends('proyectos.layout')

@section('title', 'Crear Proyecto')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Crear Nuevo Proyecto
            </h2>
        </div>
    </div>

    <form action="{{ route('proyectos.store') }}" method="POST" class="space-y-6">
        @csrf
        
        <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <div class="md:col-span-1">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Información Básica</h3>
                    <p class="mt-1 text-sm text-gray-500">Datos principales del proyecto</p>
                </div>
                <div class="mt-5 md:mt-0 md:col-span-2 space-y-6">
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre del Proyecto *</label>
                        <input type="text" name="nombre" id="nombre" required value="{{ old('nombre') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-junta-green-500 focus:border-junta-green-500 sm:text-sm">
                        @error('nombre')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="codigo" class="block text-sm font-medium text-gray-700">Código</label>
                        <input type="text" name="codigo" id="codigo" value="{{ old('codigo') }}" placeholder="Se generará automáticamente si se deja vacío" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-junta-green-500 focus:border-junta-green-500 sm:text-sm">
                        @error('codigo')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción</label>
                        <textarea name="descripcion" id="descripcion" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-junta-green-500 focus:border-junta-green-500 sm:text-sm">{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="estado" class="block text-sm font-medium text-gray-700">Estado *</label>
                            <select name="estado" id="estado" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-junta-green-500 focus:border-junta-green-500 sm:text-sm">
                                <option value="planificacion" {{ old('estado') === 'planificacion' ? 'selected' : '' }}>Planificación</option>
                                <option value="en_progreso" {{ old('estado') === 'en_progreso' ? 'selected' : '' }}>En Progreso</option>
                                <option value="pausado" {{ old('estado') === 'pausado' ? 'selected' : '' }}>Pausado</option>
                                <option value="completado" {{ old('estado') === 'completado' ? 'selected' : '' }}>Completado</option>
                                <option value="cancelado" {{ old('estado') === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                        </div>

                        <div>
                            <label for="prioridad" class="block text-sm font-medium text-gray-700">Prioridad *</label>
                            <select name="prioridad" id="prioridad" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-junta-green-500 focus:border-junta-green-500 sm:text-sm">
                                <option value="baja" {{ old('prioridad') === 'baja' ? 'selected' : '' }}>Baja</option>
                                <option value="media" selected {{ old('prioridad') === 'media' ? 'selected' : '' }}>Media</option>
                                <option value="alta" {{ old('prioridad') === 'alta' ? 'selected' : '' }}>Alta</option>
                                <option value="critica" {{ old('prioridad') === 'critica' ? 'selected' : '' }}>Crítica</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="fecha_inicio" class="block text-sm font-medium text-gray-700">Fecha de Inicio</label>
                            <input type="date" name="fecha_inicio" id="fecha_inicio" value="{{ old('fecha_inicio') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-junta-green-500 focus:border-junta-green-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="fecha_fin_estimada" class="block text-sm font-medium text-gray-700">Fecha Estimada de Finalización</label>
                            <input type="date" name="fecha_fin_estimada" id="fecha_fin_estimada" value="{{ old('fecha_fin_estimada') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-junta-green-500 focus:border-junta-green-500 sm:text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="responsable_id" class="block text-sm font-medium text-gray-700">Responsable</label>
                            <select name="responsable_id" id="responsable_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-junta-green-500 focus:border-junta-green-500 sm:text-sm">
                                <option value="">-- Seleccionar --</option>
                                @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->id }}" {{ old('responsable_id') == $usuario->id ? 'selected' : '' }}>
                                    {{ $usuario->nombre }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="color" class="block text-sm font-medium text-gray-700">Color</label>
                            <input type="color" name="color" id="color" value="{{ old('color', '#006633') }}" class="mt-1 block w-full h-10 border-gray-300 rounded-md shadow-sm focus:ring-junta-green-500 focus:border-junta-green-500">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('proyectos.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-junta-green-500">
                Cancelar
            </a>
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-junta-green-600 hover:bg-junta-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-junta-green-500">
                Crear Proyecto
            </button>
        </div>
    </form>
</div>
@endsection
