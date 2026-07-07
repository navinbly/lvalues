# Microsoft Entra External ID and Google Login Setup

## Environment variables

Set these outside source control in Apache/XAMPP, Hostinger, or your server environment:

```env
ENTRA_TENANT_ID=your-tenant-id-or-primary-domain
ENTRA_CLIENT_ID=your-entra-application-client-id
ENTRA_CLIENT_SECRET=your-entra-client-secret
ENTRA_REDIRECT_URI=http://localhost/lvalues/login/oauth-callback/entra
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
```

Optional for Entra External ID tenants using the `ciamlogin.com` authority:

```env
ENTRA_AUTHORITY=https://your-tenant-name.ciamlogin.com/your-tenant-id-or-domain
GOOGLE_REDIRECT_URI=http://localhost/lvalues/login/oauth-callback/google
```

## Microsoft Entra External ID

1. In Microsoft Entra admin center, create or open the External ID tenant.
2. Register a web application for Lvalues.
3. Add redirect URI: `http://localhost/lvalues/login/oauth-callback/entra` for local testing.
4. Add the Hostinger production callback after deployment.
5. Create a client secret and store it in `ENTRA_CLIENT_SECRET`.
6. Enable ID tokens/authorization code flow for the web app.
7. Configure sign-in methods/user flow so email, Microsoft account, and Microsoft Authenticator passwordless/MFA are allowed.
8. Enforce Authenticator number matching/passwordless in the Entra authentication methods or Conditional Access policy.

## Google

1. In Google Cloud Console, create/select a project.
2. Configure OAuth consent screen.
3. Create OAuth client ID of type Web application.
4. Add authorized redirect URI: `http://localhost/lvalues/login/oauth-callback/google`.
5. Add the Hostinger production callback after deployment.
6. Store the client ID and secret in environment variables.

## Database

The application creates `user_auth_identities` automatically on first external login. It stores provider, provider subject ID, email, name, role snapshot, raw profile JSON, and last login time.