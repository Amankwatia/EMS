@extends('layouts.voter')

@section('content')
<form method="POST" action="{{ route('voter.review') }}">
    @csrf
    <div class="mb-4 text-center">
        <h1 class="h3">{{ $election->title }}</h1>
        <p class="text-muted mb-0">{{ $voter->full_name }} | {{ $voter->student_id }}</p>
    </div>
    @foreach ($election->positions as $position)
        <section class="mb-4">
            <h2 class="h4">{{ $position->name }}</h2>
            <div class="row g-3">
                @foreach ($position->candidates as $candidate)
                    <div class="col-md-4">
                        <label class="card h-100 border-0 shadow-sm">
                            @if ($candidate->photo_path)
                                <img class="ballot-photo card-img-top" src="{{ asset('storage/'.$candidate->photo_path) }}" alt="">
                            @else
                                <div class="ballot-photo card-img-top"></div>
                            @endif
                            <div class="card-body">
                                <div class="form-check">
                                    @if ($position->max_choices > 1)
                                        <input class="form-check-input" type="checkbox" name="choices[{{ $position->id }}][]" value="{{ $candidate->id }}">
                                    @else
                                        <input class="form-check-input" type="radio" name="choices[{{ $position->id }}]" value="{{ $candidate->id }}" @required($position->is_required && ! $position->allow_abstain)>
                                    @endif
                                    <span class="form-check-label fw-semibold">{{ $candidate->candidate_name }}</span>
                                </div>
                                <div class="text-muted small">{{ $candidate->class_name }} {{ $candidate->programme }}</div>
                                @if ($candidate->manifesto)
                                    <p class="small mt-2 mb-0">{{ $candidate->manifesto }}</p>
                                @endif
                            </div>
                        </label>
                    </div>
                @endforeach
                @if ($position->allow_abstain)
                    <div class="col-md-4">
                        <label class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="form-check">
                                    @if ($position->max_choices > 1)
                                        <input class="form-check-input" type="checkbox" name="choices[{{ $position->id }}][]" value="abstain">
                                    @else
                                        <input class="form-check-input" type="radio" name="choices[{{ $position->id }}]" value="abstain" @required($position->is_required)>
                                    @endif
                                    <span class="form-check-label fw-semibold">Abstain</span>
                                </div>
                            </div>
                        </label>
                    </div>
                @endif
            </div>
        </section>
    @endforeach
    <div class="sticky-bottom bg-white border-top p-3 text-end">
        <button class="btn btn-primary btn-lg">Review Vote</button>
    </div>
</form>
@endsection
