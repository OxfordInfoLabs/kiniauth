import { Injectable } from '@angular/core';
import {
    HttpRequest,
    HttpHandler,
    HttpInterceptor, HttpErrorResponse
} from '@angular/common/http';
import { throwError } from 'rxjs/internal/observable/throwError';
import { finalize, tap } from 'rxjs/operators';
import { KiniAuthModuleConfig } from './ng-kiniauth.module';
import { AuthenticationService } from './lib/services/authentication.service';

@Injectable()
export class SessionInterceptor implements HttpInterceptor {

    constructor(private authService: AuthenticationService,
                private config: KiniAuthModuleConfig) {
    }

    intercept(request: HttpRequest<any>, next: HttpHandler) {
        request = request.clone({
            withCredentials: true
        });

        if (!request.url.startsWith('http')) {
            request = request.clone({
                url: this.config.accessHttpURL + request.url
            });
        }

        if (!request.headers.has('Content-Type')) {
            request = request.clone({ headers: request.headers.set('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8') });
        }

        const sessionData = this.authService.sessionData.getValue();
        if (sessionData && sessionData.csrfToken) {
            request = request.clone({ headers: request.headers.set('x-csrf-token', sessionData.csrfToken) });
        }


        return next.handle(request)
            .pipe(
                tap(
                    event => {
                    },
                    error => {
                        if (error instanceof HttpErrorResponse) {
                            return throwError(error);
                        }
                    }
                ),
                finalize(() => {
                })
            );
    }

}
