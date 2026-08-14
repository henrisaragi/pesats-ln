@php
    $modalId = 'user-'.$prefix.'-modal';
    $isRedisplay = old('_modal') === $modalId;
    $isSuperAdmin = auth()->user()->hasRole('super-admin');
    $viewerRoleName = auth()->user()->roles->first()?->name;
@endphp

<div class="p-4 overflow-y-auto max-h-[70vh]">
    <div class="flex flex-col gap-y-4">
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="user-{{ $prefix }}-name">{{ __('Nama') }}</label>
            <input class="form-input" id="user-{{ $prefix }}-name" name="name" type="text"
                   value="{{ $isRedisplay ? old('name') : '' }}"/>
            @if ($isRedisplay && $errors->has('name'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
            @endif
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="user-{{ $prefix }}-username">{{ __('Username') }}</label>
            <input autocomplete="off" class="form-input" data-check-url="{{ route('users.check-username') }}"
                   data-username-check id="user-{{ $prefix }}-username" name="username" type="text"
                   value="{{ $isRedisplay ? old('username') : '' }}"/>
            @if ($isRedisplay && $errors->has('username'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('username') }}</p>
            @else
                <p class="mt-1 text-xs text-danger hidden" data-username-error></p>
            @endif
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="user-{{ $prefix }}-email">{{ __('Email') }}</label>
            <input class="form-input" id="user-{{ $prefix }}-email" name="email" type="email"
                   value="{{ $isRedisplay ? old('email') : '' }}"/>
            @if ($isRedisplay && $errors->has('email'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('email') }}</p>
            @endif
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="user-{{ $prefix }}-password">
                {{ __('Password') }}
                @if ($prefix === 'edit')
                    <span class="text-default-400 text-xs font-normal">({{ __('kosongkan jika tidak diubah') }})</span>
                @endif
            </label>
            <input autocomplete="new-password" class="form-input" id="user-{{ $prefix }}-password" name="password"
                   type="password" value="{{ $isRedisplay ? old('password') : '' }}"/>
            @if ($isRedisplay && $errors->has('password'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('password') }}</p>
            @endif
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="user-{{ $prefix }}-entity">{{ __('Entitas') }}</label>
            @if ($isSuperAdmin)
                <select class="form-input" id="user-{{ $prefix }}-entity" name="entity_id">
                    <option value="">{{ __('Pilih Entitas') }}</option>
                    @foreach ($entityOptions as $entityOption)
                        <option value="{{ $entityOption->id }}"
                                @selected($isRedisplay && (int) old('entity_id') === $entityOption->id)>
                            {{ $entityOption->name }}
                        </option>
                    @endforeach
                </select>
                @if ($isRedisplay && $errors->has('entity_id'))
                    <p class="mt-1 text-xs text-danger">{{ $errors->first('entity_id') }}</p>
                @endif
            @else
                <select class="form-input bg-default-100 text-default-500 cursor-not-allowed" disabled>
                    <option selected>{{ auth()->user()->entity?->name ?? __('Tidak ada entitas') }}</option>
                </select>
                <input name="entity_id" type="hidden" value="{{ auth()->user()->entity_id }}">
            @endif
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="user-{{ $prefix }}-nip">{{ __('NIP') }}</label>
            <input class="form-input" id="user-{{ $prefix }}-nip" name="nip" type="text"
                   value="{{ $isRedisplay ? old('nip') : '' }}"/>
            @if ($isRedisplay && $errors->has('nip'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('nip') }}</p>
            @endif
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="user-{{ $prefix }}-jabatan">{{ __('Jabatan') }}</label>
            <input class="form-input" id="user-{{ $prefix }}-jabatan" name="jabatan" type="text"
                   value="{{ $isRedisplay ? old('jabatan') : '' }}"/>
            @if ($isRedisplay && $errors->has('jabatan'))
                <p class="mt-1 text-xs text-danger">{{ $errors->first('jabatan') }}</p>
            @endif
        </div>
        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="user-{{ $prefix }}-role">{{ __('Role') }}</label>
            @if ($isSuperAdmin)
                <select class="form-input" id="user-{{ $prefix }}-role" name="role">
                    <option value="">{{ __('Pilih Role') }}</option>
                    @foreach ($roleOptions as $roleOption)
                        <option value="{{ $roleOption->name }}"
                                @selected($isRedisplay && old('role') === $roleOption->name)>
                            {{ $roleOption->name }}
                        </option>
                    @endforeach
                </select>
                @if ($isRedisplay && $errors->has('role'))
                    <p class="mt-1 text-xs text-danger">{{ $errors->first('role') }}</p>
                @endif
            @else
                <select class="form-input bg-default-100 text-default-500 cursor-not-allowed" disabled>
                    <option selected>{{ $viewerRoleName ?? '-' }}</option>
                </select>
                <input name="role" type="hidden" value="{{ $viewerRoleName }}">
            @endif
        </div>
        <div class="flex items-center gap-2">
            <input name="is_active" type="hidden" value="0">
            <input class="form-checkbox" id="user-{{ $prefix }}-is-active" name="is_active" type="checkbox"
                   value="1"
                   @checked($isRedisplay ? old('is_active') : $prefix === 'create')/>
            <label class="text-sm font-medium" for="user-{{ $prefix }}-is-active">{{ __('Aktif') }}</label>
        </div>
    </div>
</div>
