@component('shop::emails.layout')
    <div style="margin-bottom: 34px;">
        <p style="font-weight: bold;font-size: 20px;color: #121A26;line-height: 24px;margin-bottom: 24px">
            @lang('shop::app.emails.dear', ['customer_name' => $customer->name]), 👋
        </p>

        <p style="font-size: 16px;color: #384860;line-height: 24px;">
            @lang('shop::app.emails.customers.account-created.greeting')
        </p>
    </div>

    <p style="font-size: 16px;color: #384860;line-height: 24px;margin-bottom: 24px">
        @lang('shop::app.emails.customers.account-created.description')
    </p>

    <p style="font-size: 16px;color: #384860;line-height: 24px;margin-bottom: 8px">
        @lang('shop::app.emails.customers.account-created.credentials-description')
    </p>

    <table style="margin-bottom: 40px;border-collapse: collapse;">
        <tr>
            <td style="font-size: 14px;color: #384860;padding: 6px 16px 6px 0;font-weight: 600;">
                @lang('shop::app.emails.customers.account-created.username-email'):
            </td>
            <td style="font-size: 14px;color: #384860;padding: 6px 0;">
                {{ $customer->email }}
            </td>
        </tr>
        <tr>
            <td style="font-size: 14px;color: #384860;padding: 6px 16px 6px 0;font-weight: 600;">
                @lang('shop::app.emails.customers.account-created.password'):
            </td>
            <td style="font-size: 14px;color: #384860;padding: 6px 0;">
                {{ $password }}
            </td>
        </tr>
    </table>

    <div style="display: flex;margin-bottom: 95px">
        <a
            href="{{ route('shop.customer.session.index') }}"
            style="padding: 16px 45px;justify-content: center;align-items: center;gap: 10px;border-radius: 2px;background: #060C3B;color: #FFFFFF;text-decoration: none;text-transform: uppercase;font-weight: 700;"
        >
            @lang('shop::app.emails.customers.account-created.sign-in')
        </a>
    </div>
@endcomponent
