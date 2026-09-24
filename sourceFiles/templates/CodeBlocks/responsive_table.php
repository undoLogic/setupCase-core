
<style>
    .stack-on-sm {
        width: 100%;
    }

    @media (max-width: 640px) {
        .stack-on-sm thead { display: none; }

        .stack-on-sm,
        .stack-on-sm tbody,
        .stack-on-sm tr,
        .stack-on-sm td {
            display: block; width: 100%;
        }

        .stack-on-sm tr { margin: 0 0 0.75rem; }

        .stack-on-sm td {
            position: relative;
            padding-left: 9rem; /* space for the label */
            text-align: left;
        }

        .stack-on-sm td::before {
            content: attr(data-label);
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 8.5rem;          /* label column width */
            padding: 0.5rem 0.75rem;
            font-weight: 600;
            display: flex; align-items: center;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
    }

</style>

<table class="table stack-on-sm">
    <thead>
    <tr>
        <th>
            ONE
        </th>
        <th>
            TWO
        </th>
        <th>
            THREE
        </th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td data-label="ONE">
            1
        </td>
        <td data-label="TWO">
            2
        </td>
        <td data-label="THREE">
            3
        </td>
    </tr>
    <tr>
        <td data-label="ONE">
            11
        </td>
        <td data-label="TWO">
            22
        </td>
        <td data-label="THREE">
            33
        </td>
    </tr>
    <tr>
        <td data-label="ONE">
            111
        </td>
        <td data-label="TWO">
            222
        </td>
        <td data-label="THREE">
            333
        </td>
    </tr>
    </tbody>
</table>
