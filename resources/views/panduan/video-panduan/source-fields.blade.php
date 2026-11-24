<div>
    <label class="inline-block mb-2 text-sm font-medium" for="{{ $formId }}-video-url">{{ __('Link Video (YouTube atau link file video)') }}</label>
    <input class="form-input" id="{{ $formId }}-video-url" name="video_url" placeholder="https://www.youtube.com/watch?v=..." type="url"
           value="{{ $isRedisplay ? old('video_url') : $item?->video_url }}"/>
    @if ($isRedisplay && $errors->has('video_url'))
        <p class="mt-1 text-xs text-danger">{{ $errors->first('video_url') }}</p>
    @endif
</div>
<div>
    <label class="inline-block mb-2 text-sm font-medium" for="{{ $formId }}-file">{{ __('atau Upload Video (MP4/WEBM, maks. 100 MB)') }}</label>
    @if ($item?->file_path)
        <p class="mb-2 text-xs text-default-500">
            {{ __('File saat ini') }}: {{ $item->original_filename }} — {{ __('kosongkan jika tidak diubah') }}
        </p>
    @endif
    <input accept="video/mp4,video/webm" class="form-input" id="{{ $formId }}-file" name="file" type="file"/>
    <p class="mt-1 text-xs text-default-500">{{ __('Jika upload file, link video diabaikan.') }}</p>
    @if ($isRedisplay && $errors->has('file'))
        <p class="mt-1 text-xs text-danger">{{ $errors->first('file') }}</p>
    @endif
</div>
