

<h2>
    Responsive Table
</h2>

<style>
    table {
        border: 1px solid #ccc;
        border-collapse: collapse;
        margin: 0;
        padding: 0;
        width: 100%;
        table-layout: fixed;
    }



    table tr {
        background-color: #f8f8f8;
        border: 1px solid #ddd;
        padding: .35em;
    }

    table th,
    table td {
        padding: .625em;
        text-align: center;
    }

    table th {
        padding: 14px 10px;
        font-size: 9px;
        line-height: 1.6;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        font-family: "Roboto", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif;
        color: #ffffff;
        background: #403254;
    }
    input{
        max-width: 140px;
        text-align: right;
    }




    @media screen and (max-width: 600px) {
        table {
            border: 0;
        }

        table caption {
            font-size: 1.3em;
        }

        table thead {
            border: none;
            clip: rect(0 0 0 0);
            height: 1px;
            margin: -1px;
            overflow: hidden;
            padding: 0;
            position: absolute;
            width: 1px;
            color: #ffffff;
            background: #403254;
        }

        table tr {
            border-bottom: 3px solid #ddd;
            display: block;
            margin-bottom: .625em;
        }

        table td {
            border-bottom: 1px solid #ddd;
            display: block;
            font-size: .8em;
            text-align: right;
        }

        table td::before {
            /*
            * aria-label has no advantage, it won't be read inside a table
            content: attr(aria-label);
            */
            content: attr(data-label);
            float: left;
            font-weight: bold;
            text-transform: uppercase;
        }

        table td:last-child {
            border-bottom: 0;
        }



    }


    /* general styling */
    body {
        font-family: "Open Sans", sans-serif;
        line-height: 1.25;
    }


</style>


<table>
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

