@php
    $groupedCategories = $categories->groupBy('parent_id');

    $currentCategories = $groupedCategories->get(0);
@endphp

@if($currentCategories)
    @foreach ($currentCategories as $category)
        @php
            $hasChildren = $groupedCategories->has($category->id);
        @endphp
        @if ((!$more && $loop->index < 10) || ($more && $loop->index >= 10))
            <li @if ($hasChildren) class="has-children" @endif>
                <a href="{{ route('public.single', $category->url) }}">
                    @if ($iconImage = $category->icon_image)
                        <img src="{{ RvMedia::getImageUrl($iconImage) }}" alt="{{ $category->name }}" width="18" height="18">
                    @elseif ($icon = $category->icon)
                        {!! BaseHelper::renderIcon($icon) !!}
                    @endif {{ $category->name }}
                </a>

                @if ($hasChildren)
                    @php
                        $currentCategories = $groupedCategories->get($category->id);
                    @endphp
                    @if($currentCategories)
                        <div class="dropdown-menu">
                            <ul>
                                @foreach ($currentCategories as $childCategory)
                                    @php
                                        $hasChildren = $groupedCategories->has($childCategory->id);
                                    @endphp
                                    <li @if ($hasChildren) class="has-children" @endif>
                                        <a class="dropdown-item nav-link nav_item" href="{{ route('public.single', $childCategory->url) }}">{{ $childCategory->name }}</a>

                                        @if ($hasChildren)
                                            @php
                                                $currentCategories = $groupedCategories->get($childCategory->id);
                                            @endphp
                                            @if($currentCategories)
                                                <div class="dropdown-menu">
                                                    <ul>
                                                        @foreach ($currentCategories as $item)
                                                            <li>
                                                                <a class="dropdown-item nav-link nav_item" href="{{ route('public.single', $item->url) }}">{{ $item->name }}</a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endif
            </li>
        @endif
    @endforeach
@endif
