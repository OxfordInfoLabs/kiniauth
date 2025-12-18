import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SsoInitialisationComponent } from './sso-initialisation.component';

describe('SsoInitialisationComponent', () => {
  let component: SsoInitialisationComponent;
  let fixture: ComponentFixture<SsoInitialisationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SsoInitialisationComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SsoInitialisationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
