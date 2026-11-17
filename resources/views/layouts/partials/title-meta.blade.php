<meta charset="utf-8" />
<title>{{ $title }} | Ditjen KSDAE - Kementerian Kehutanan</title>
<meta content="{{ csrf_token() }}" name="csrf-token" />
<meta content="{{ url('/') }}" name="app-url" />
<meta content="width=device-width, initial-scale=1.0" name="viewport" />
<script>
    window.dataTableLanguage = {
        processing: "{{ __('Memuat...') }}",
        search: "{{ __('Cari:') }}",
        lengthMenu: "{{ __('Tampilkan _MENU_ data') }}",
        info: "{{ __('Menampilkan _START_ - _END_ dari _TOTAL_ data') }}",
        infoEmpty: "{{ __('Tidak ada data') }}",
        infoFiltered: "{{ __('(disaring dari _MAX_ total data)') }}",
        loadingRecords: "{{ __('Memuat...') }}",
        zeroRecords: "{{ __('Data tidak ditemukan') }}",
        emptyTable: "{{ __('Belum ada data.') }}",
        paginate: {
            first: "{{ __('Awal') }}",
            last: "{{ __('Akhir') }}",
            next: "{{ __('Selanjutnya') }}",
            previous: "{{ __('Sebelumnya') }}",
        },
    };
</script>
@auth
    <script>
        window.authUserId = {{ Auth::id() }};
    </script>
@endauth
<meta content="Portal Elektronik Surat Angkut Tumbuhan dan Satwa Liar" name="description" />
<meta content="PeSATS" name="kemenhut" />
<!-- App favicon -->
<link href="{{ asset('images/favicon.ico') }}" rel="shortcut icon" />
